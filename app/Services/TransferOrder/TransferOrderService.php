<?php


namespace App\Services\TransferOrder;

use App\Exceptions\Transfer\TransferValidationException;
use App\Http\Resources\Transfer\NewTransferOrderResource;
use App\Logging\TelegramLogger;
use App\Models\TransferOrder;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Services\DY365\DyService;
use App\Services\Logs\TechnicianLogService;
use App\Services\Telegram\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferOrderService
{
    public function __construct(
        protected TransferOrderInterface $repo,
        protected DyService $dyService
    ) {}

    public function createTransfer(array $data)
    {

        // check if type = WarehouseToTechnician and get items quantity from warehouse stock and check product limit for technician
        if ($data['type'] === 'WarehouseToTechnician') {
            $this->validateWarehouseToTechnicianStock($data);
        } elseif ($data['type'] === 'TechnicianToTechnician') {
            $this->validateTechnicianToTechnicianStock($data);
        }

        $payload = $this->mapToDyPayload($data);

        TelegramService::send(
            "📩 CreateTransferOrder payload \n\n" .
                json_encode($payload, JSON_PRETTY_PRINT)
        );
        // expected NewTransferOrderResource.
        $response = $this->dyService->createTransferOrder($payload);

        if (
            !$response ||
            !isset($response['Status']) ||
            $response['Status'] !== true
        ) {
            throw new \Exception(
                $response['Error'] ?? 'Failed to create transfer order'
            );
        }
        $transfer = $this->CreateTransferOnDb($data, $response);
        if ($data['type'] === 'TechnicianToTechnician') {
            $this->notifyFromTechnicianForConfirmation($data, $transfer);
        }
        return $response;
    }

    /**
     * Notifies the FROM technician (looked up by fromWarehouseId) that a
     * TechnicianToTechnician transfer was created and needs their
     * confirmation or rejection. Silently logs and continues if the
     * technician or their fcm_token can't be found — a missing notification
     * shouldn't fail an already-successful transfer creation.
     */
    private function notifyFromTechnicianForConfirmation(array $data, $transfer): void
    {
        $fromWarehouseId = $data['fromWarehouseId'] ?? null;

        if (!$fromWarehouseId) {
            return;
        }

        $fromTechUser = \App\Models\User::where('warehouse_id', $fromWarehouseId)
            ->where('type', 'tech')
            ->whereNotNull('fcm_token')
            ->first();

        if (!$fromTechUser) {
            \Illuminate\Support\Facades\Log::warning('TechnicianToTechnician transfer: FROM technician not found or has no fcm_token.', [
                'transfer_id'       => $transfer->id ?? null,
                'from_warehouse_id' => $fromWarehouseId,
            ]);
            return;
        }

        $fromTechUser->notify(new \App\Notifications\TechNotification(
            'A technician-to-technician transfer has been created and needs your action.',
            'Transfer Confirmation Needed',
            'transfer_confirmation_required',
            [
                'transfer_id' => $transfer->transfer_id,
                'action'      => 'confirm_or_reject',
            ]
        ));
    }


    /**
     * TechnicianToTechnician validation:
     *   - Every requested item+quantity must be AVAILABLE on the FROM warehouse.
     *   - Every requested item must be NOT found (or quantity = 0) on the TO
     *     warehouse — the receiving technician shouldn't already be holding
     *     stock of an item they're about to receive more of via this transfer.
     *
     * ⚠ Assumes the DY365 stock response's quantity field is named
     * 'Quantity' on each product row, matching the pattern used elsewhere in
     * this codebase (buildStockMapForSalesLines). Adjust findWarehouseProduct()
     * below if the real key differs.
     */
    private function validateTechnicianToTechnicianStock(array $data): void
    {
        $fromWarehouseId = $data['fromWarehouseId'];
        $toWarehouseId   = $data['toWarehouseId'];
        $errors = [];
        // dd($data);
        foreach ($data['items'] as $item) {
            $itemNumber = trim((string) $item['ItemNumber']);
            $quantity   = (float) $item['Quantity'];

            // ── TO warehouse: item must NOT already be in stock there ──────
            $toProduct  = $this->findWarehouseProduct($toWarehouseId, $itemNumber);
            $toQuantity = (float) ($toProduct['Quantity'] ?? 0);

            // if ($toProduct !== null && $toQuantity > 0) {
            //     $errors[] = "Item {$itemNumber} already exists in the destination warehouse stock (quantity: {$toQuantity}).";
            // }

            // ── FROM warehouse: item + quantity must be available ──────────
            $fromProduct  = $this->findWarehouseProduct($fromWarehouseId, $itemNumber);
            $fromQuantity = (float) ($fromProduct['Quantity'] ?? 0);

            if ($fromProduct === null || $fromQuantity <= 0) {
                $errors[] = "Item {$itemNumber} is not available in the source warehouse stock.";
            } elseif ($fromQuantity < $quantity) {
                $errors[] = "Insufficient stock for item {$itemNumber} in source warehouse. Available: {$fromQuantity}, Requested: {$quantity}.";
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * Looks up a single product by exact ItemNumber match within a specific
     * warehouse's stock. Uses searchTerm to narrow the DY365 response rather
     * than pulling the entire warehouse inventory just to check one item —
     * then filters for an exact (case-insensitive) match, since searchTerm's
     * matching behavior on the DY365 side isn't guaranteed to be exact.
     */
    private function findWarehouseProduct(string $warehouseId, string $itemNumber): ?array
    {
        $payload = [
            'warehouseId' => $warehouseId,
            'searchTerm'  => $itemNumber,
            'currentPage' => 1,
            'pageSize'    => 100,
        ];

        $response = $this->dyService->getWarehouseStockNew($payload, 0);

        if ($response === null) {
            throw new \Exception('Dynamics warehouse stock is unavailable right now. Please try again.');
        }

        $products = $response['Data']['Products'] ?? [];
        $products = is_array($products) ? $products : [];

        foreach ($products as $product) {
            if (strtolower(trim((string) ($product['ItemNumber'] ?? ''))) === strtolower($itemNumber)) {
                return $product;
            }
        }

        return null;
    }

    public function updateStatus(array $data)
    {
        $technicianType = match ($data['type']) {
            'WarehouseToTechnician' => 'To',
            'TechnicianToWarehouse' => 'From',
            default => $data['TechnicianType'] ?? null,
        };

        $contract = [
            'TransferId'       => $data['TransferId'],
            'Type'             => $data['type'],
            'TechnicianStatus' => $data['TechnicianStatus'],
            'TechnicianType'   => $technicianType,
        ];

        // When it's a tech-to-tech transfer and this side is the receiving
        // ("To") technician, items are not sent at all — the sending side
        // ("From") is the one that reports what's being transferred.

        if ($technicianType == 'From') {
            $omitItems = $data['type'] === 'TechnicianToTechnician' && ($data['TechnicianType'] ?? null) === 'To';

            if (! $omitItems) {
                $contract['items'] = collect($data['items'])->map(fn($item) => [
                    'ItemNumber' => $item['ItemNumber'],
                    'Id' => $item['Id'],
                    'Quantity'   => $item['Quantity'],
                    'ItemSerials'  => $item['SerialNum'] ?? [],
                ])->values()->toArray();
            }
        }

        $payload = ['_contract' => $contract];
        TelegramService::send(
            "📩 updateTransferOrder payload \n\n" .
                json_encode($payload, JSON_PRETTY_PRINT)
        );

        // ⚠ FIXED: this was `return $response = $this->dyService->updateTransferOrder($payload);`
        // — a stray early return that exited the function immediately
        // after calling DY365. Everything below it (the success check
        // and the notifyToTechnicianOfDecision() call) was 100%
        // unreachable dead code — meaning the TO technician was NEVER
        // actually being notified of the FROM technician's confirm/reject
        // decision, regardless of whether DY365 succeeded or failed.
        $response = $this->dyService->updateTransferOrder($payload);

        $isSuccess = is_array($response) && ($response['Status'] ?? false) === true;

        if (
            $isSuccess &&
            $data['type'] === 'TechnicianToTechnician' &&
            ($data['TechnicianType'] ?? null) === 'From'
        ) {
            $this->notifyToTechnicianOfDecision($data);
        }

        return $response;
    }
    /**
     * Notifies the TO technician (looked up by toWarehouseId) that the FROM
     * technician has confirmed or rejected a TechnicianToTechnician transfer.
     * Silently logs and continues if the technician or their fcm_token can't
     * be found — a missing notification shouldn't fail an already-successful
     * status update.
     */
    private function notifyToTechnicianOfDecision(array $data): void
    {
        $toWarehouseId = $data['toWarehouseId'] ?? null;

        if (!$toWarehouseId) {
            return;
        }

        $toTechUser = \App\Models\User::where('warehouse_id', $toWarehouseId)
            ->where('type', 'tech')
            ->whereNotNull('fcm_token')
            ->first();

        if (!$toTechUser) {
            \Illuminate\Support\Facades\Log::warning('TechnicianToTechnician transfer: TO technician not found or has no fcm_token.', [
                'transfer_id'    => $data['TransferId'] ?? null,
                'to_warehouse_id' => $toWarehouseId,
            ]);
            return;
        }

        $decision = $data['TechnicianStatus']; // 'Confirmed' or 'Rejected'

        $message = $decision === 'Confirmed'
            ? 'The other technician has confirmed the transfer.'
            : 'The other technician has rejected the transfer.';

        $toTechUser->notify(new \App\Notifications\TechNotification(
            $message,
            'Transfer ' . $decision,
            'transfer_decision',
            [
                'transfer_id' => $data['TransferId'],
                'decision'    => $decision,
            ]
        ));
    }


    public function delete($transferOrderId)
    {
        // $order = TransferOrder::where('transfer_id', $transferOrderId)
        //     ->orderByDesc('id')
        //     ->first();

        $payload = [
            '_contract' => [
                'TransferId' => $transferOrderId,
            ],
        ];

        // Delete only if record exists
        // if ($order) {
        //     $this->repo->delete($order->id);
        // }

        return $this->dyService->deleteTransferOrder($payload);
    }

    private function mapToDyPayload(array $data): array
    {

        return [
            '_contract' => [
                'Type'                     => $data['type'],
                'TechnicianPersonnelNumber' => $data['TechnicianPersonnelNumber'],
                'FromWarehouseId'          => $data['fromWarehouseId'],
                'ToWarehouseId'            => $data['toWarehouseId'],

                'items' => collect($data['items'])->map(fn($item) => [
                    'ItemNumber' => $item['ItemNumber'],
                    'Quantity'   => $item['Quantity'],
                ])->values()->toArray(),
            ],
        ];
    }

    public function CreateTransferOnDb(array $data, array $response): TransferOrder
    {
        return DB::transaction(function () use ($response, $data) {
            // dd($response, $data);
            $res = $response['Data'];
            $order = TransferOrder::create([
                'tech_id' => Auth()->id(),
                'transfer_rec_id' => $res['TransferRecId'],
                'transfer_id' => $res['TransferId'],
                'type' => $res['Type'],
                'status' => $res['Status'],
                'technician_status' => $res['TechnicianStatus'],
                'date' => Carbon::parse($res['CreationDate']),
                'from_warehouse' => $data['fromWarehouseId'],
                'to_warehouse' => $data['toWarehouseId'],
                // flags
                'dy_synced' => 1,
                'dy_response' => json_encode($response),
            ]);

            foreach ($res['Items'] as $item) {
                $order->lines()->create([
                    'item_number' => $item['ItemId'],
                    'requested_quantity' => $item['RequestedQuantity'],
                    'transferred_quantity' => $item['TransferredQuantity'],
                    'quantity' => $item['RequestedQuantity'],
                    'type' => 'item',
                ]);
            }

            return $order;
        });
    }

    // tech transfers

    public function getTechnicianTransfersWithLocalStatus($techId, $currentPage = 1, $pageSize = 30, $searchTerm = '')
    {
        $payload = [
            'worker' => $techId,
            'currentPage' => $currentPage,
            'pageSize' => $pageSize,
            'searchTerm' => $searchTerm,
        ];

        $response = $this->dyService->getTechnicianTransfers($payload);

        if (!($response['Status'] ?? false)) {
            Log::channel('dy')->error('DY error', $response);
            return false;
        }

        $transfers = data_get($response, 'Data', []);

        return     $mappedTransfers = $this->repo->mapTechnicianStatusFromDb(
            $transfers
        );
    }

    // single transfer order
    public function getSingleTransferAndSyncTechStatus($techId, $transferOrderId)
    {
        $payload = [
            'worker'    => $techId,
            'transferId' => $transferOrderId,
        ];

        $transfer = $this->dyService->getSingleTransferOrder($payload);

        // لو DY فشل رجّع زي ما هو
        if (!($transfer['Status'] ?? false)) {
            return $transfer;
        }

        // Get transfer order lines
        $items = data_get($transfer, 'Data.TransferOrderLines', []);

        foreach ($items as $index => &$item) {

            $serialsResponse = $this->dyService->getWarehouseStockNew([
                'warehouseId' => $transfer['Data']['FromWarehouse'],
                'searchTerm'  => $item['ItemId'],
                'currentPage' => 1,
                'pageSize'    => 3,
            ]);

            $item['AvailableSerials'] = data_get(
                $serialsResponse,
                'Data.Products.0.ProductsPerSerial',
                []
            );
        }

        unset($item);

        // Replace TransferOrderLines with the modified items
        data_set($transfer, 'Data.TransferOrderLines', $items);
        return $transfer;
        // Pass transfer response to repository for mapping
        return $this->repo->mapSingleTechFromDb($transfer);
    }

    private function validateWarehouseToTechnicianStock(array $data): void
    {
        $categoryTotals = [];
        $errors = [];
        return;
        foreach ($data['items'] as $item) {
            $stockResponse = $this->dyService->getWarehouseStockNew([
                'warehouseId' => $data['fromWarehouseId'],
                'searchTerm' => $item['ItemNumber'],
                'currentPage' => 1,
                'pageSize' => 1,
            ], 0);

            if (!($stockResponse['Status'] ?? false)) {
                $errors[] = [
                    'item' => $item['ItemNumber'],
                    'field' => 'stock',
                    'message' => 'Failed to get warehouse stock for item ' . $item['ItemNumber'],
                ];
                continue;
            }

            $availableStock = $stockResponse['Data']['Products'][0]['Quantity'] ?? 0;

            $productLimitResponse = $this->dyService->getTechnicianProductLimit([
                '_worker' => $data['tech_id'],
                '_itemId' => $item['ItemNumber'],
            ]);

            if (!($productLimitResponse['Status'] ?? false)) {
                $errors[] = [
                    'item' => $item['ItemNumber'],
                    'field' => 'product_limit',
                    'message' => 'Failed to get product limit for item ' . $item['ItemNumber'],
                ];
                continue;
            }

            $category = $productLimitResponse['Data']['Category'] ?? null;
            $remain = $productLimitResponse['Data']['Remain'] ?? 0;
            $minAllowed = min($availableStock, $remain);

            if ($item['Quantity'] > $minAllowed) {
                $errors[] = [
                    'item' => $item['ItemNumber'],
                    'field' => 'quantity',
                    'message' => "Requested quantity ({$item['Quantity']}) exceeds allowed limit ({$minAllowed}).",
                    'available_stock' => $availableStock,
                    'remain' => $remain,
                    'min_allowed' => $minAllowed,
                ];
            }

            if ($category !== null) {
                if (!isset($categoryTotals[$category])) {
                    $categoryTotals[$category] = ['total' => 0, 'remain' => $remain];
                }
                $categoryTotals[$category]['total'] += $item['Quantity'];
            }
        }

        foreach ($categoryTotals as $category => $cat) {
            if ($cat['total'] > $cat['remain']) {
                $errors[] = [
                    'category' => $category,
                    'field' => 'category_limit',
                    'message' => "Total quantity ({$cat['total']}) for category '{$category}' exceeds technician remain ({$cat['remain']}).",
                    'total' => $cat['total'],
                    'remain' => $cat['remain'],
                ];
            }
        }

        if (!empty($errors)) {
            throw new TransferValidationException($errors);
        }
    }
}
