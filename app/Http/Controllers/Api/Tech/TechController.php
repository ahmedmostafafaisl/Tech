<?php

namespace App\Http\Controllers\Api\Tech;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Helper\ApiResponseHelper;
use App\Services\DY365\DyService;
use App\Services\ValidationService;
use App\Http\Controllers\Controller;
use App\Jobs\SyncTechnicianStockJob;
use App\Http\Resources\Tech\HomeResource;
use App\Http\Resources\Tech\TechResource;
use App\Http\Resources\User\UserStockResource;
use App\Http\Resources\User\MergedUserStockResource;
use App\Http\Requests\Tech\AppointmentSummaryRequest;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Repositories\Interfaces\UserStockRepositoryInterface;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;

class TechController extends Controller
{
    use ApiResponseHelper;

    protected $techRepository;
    private $validationService;
    private   $userStockRepository;
    private   $dyService;
    private  $appointmentRepo;
    private $transferOrderRepo;

    public function __construct(AppointmentRepositoryInterface $appointmentRepo, TechRepositoryInterface $techRepository, ValidationService $validationService, DyService $dyService, UserStockRepositoryInterface $userStockRepository, TransferOrderInterface $transferOrderRepo)
    {
        $this->appointmentRepo = $appointmentRepo;
        $this->techRepository = $techRepository;
        $this->validationService = $validationService;
        $this->dyService = $dyService;
        $this->userStockRepository = $userStockRepository;
        $this->transferOrderRepo = $transferOrderRepo;
    }


    public function getDayAppointments()
    {
        $user = auth()->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }
        $data = $this->techRepository->getTodayAppointmentsSummary($user);

        return $this->setCode(code: 200)->setData(new TechResource($data))->setMessage('Success.')->send();
    }
    public function getTechHome()
    {

        $user = auth()->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }
        $data = $this->techRepository->getTodayAppointmentsSummary($user);

        return $this->setCode(code: 200)->setData(new HomeResource($data))->setMessage('Success.')->send();
    }

    public function getTechStock(Request $request)
    {
        $filter = request()->query('filter');
        $search = request()->query('search');
        $user = auth()->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }

        $data = $this->techRepository->getTechStock($user, $search, $filter);

        return $this->setCode(code: 200)->setData(new MergedUserStockResource($data, $filter))->setMessage('Success.')->send();
    }

    public function getSummaryBetweenDates(AppointmentSummaryRequest $request)
    {
        $user = auth()->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }
        $summary = $this->techRepository->getSummaryBetweenDates($user, $request->from, $request->to);
        return $this->setCode(code: 200)->setData($summary)->setMessage('Success.')->send();
    }

    public function getTodayAndTomorrowSummary(Request $request)
    {
        $dateType = $request->input('date_type', 'today');
        $search = $request->input('search');
        $user = auth()->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }
        $data = $this->techRepository->getTodayAndTomorrowSummary($user, $search, $dateType);

        return $this->setCode(code: 200)->setData($data)->setMessage('Success.')->send();
    }

    // tech notifications

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->get();

        $formatted = $notifications->map(function ($notification) {
            $data = $notification->data;

            return [
                'id' => $notification->id,
                'title' => $data['title'] ?? '',
                'message' => $data['message'] ?? '',
                'type' => $data['type'] ?? '',
                'created_at_ago' => $notification->created_at
                    ? Carbon::parse($notification->created_at)->diffForHumans()
                    : '',
                'is_read' => !is_null($notification->read_at),
            ];
        });

        $unreadCount = $user->unreadNotifications()->count();

        return $this->setCode(200)->setData([
            'unread_count' => $unreadCount,
            'notifications' => $formatted,
        ])->setMessage('Success.')->send();
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $this->techRepository->markAllAsRead($user);
        return $this->setCode(code: 200)->setData([])->setMessage('All notifications marked as read.')->send();
    }

    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $this->techRepository->markAsRead($user, $id);
        return $this->setCode(code: 200)->setData([])->setMessage('Notification marked as read.')->send();
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();

        $user->update([
            'fcm_token' => $request->input('fcm_token'),
        ]);

        return $this->setCode(code: 200)->setData([])->setMessage('FCM token updated successfully.')->send();
    }
    public function SyncTechFunctions(Request $request)
    {
        $request->validate([
            'function' => 'required|string|in:transfers,appointments',
        ]);
        $user = $request->user();
        $techId = $user->tech_id ?? $user->technician_rec_id;
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)->setData([])->setMessage('User not authenticated Or  Only technicians can access this resource.')->send();
        }

        if ($request->function === "appointments") {
            // Sync appointments
            return $this->syncTechnicianAppointments($techId);
        } elseif ($request->function === "stock") {
            $techId = $user->tech_id ?? $user->technician_rec_id;
            // Run command in background
            $cmd = "php " . base_path('artisan') . " sync:technician-stock " . escapeshellarg($techId) . " > /dev/null 2>&1 &";
            exec($cmd);

            return response()->json([
                'status'  => true,
                'message' => "Technician stock sync started in background for tech_id: {$techId}",
            ]);
        } elseif ($request->function === "transfers") {
            // Sync requests
            return $this->syncTechnicianTransfers($user);
        }

        return $this->setCode(code: 200)->setData([])->setMessage('Tech functions synced successfully.')->send();
    }

    protected function syncTechnicianStock(User $technician): array
    {
        $page = 1;
        $pageSize = 700;
        $totalSynced = 0;

        return [
            'status' => 'success',
            'technician_id' => $technician->id,
            'total_synced' => $totalSynced,
            'message' => "Synced $totalSynced stock items successfully."
        ];
        try {
            do {

                $payload = [
                    'warehouseId'  => $technician->warehouse_id,
                    'itemNumber'   => "",
                    'currentPage'  => $page,
                    'pageSize'     => $pageSize,
                ];
                $response = $this->dyService->getWarehouseStock($payload);

                $pagination = $response['Data'] ?? [];
                $products   = $pagination['Products'] ?? [];

                if (empty($products)) {
                    $this->warn("⚠️ No products found for technician {$technician->id} on page $page.");
                    break;
                }
                $this->userStockRepository->syncTechnicianStock($products, $technician->id);
                $totalSynced += count($products);
                $currentPage = $pagination['CurrentPage'] ?? $page;
                $pagesTotal  = $pagination['PagesTotal'] ?? $page;
                $page++;
            } while ($currentPage < $pagesTotal);
            return [
                'status' => 'success',
                'technician_id' => $technician->id,
                'total_synced' => $totalSynced,
                'message' => "Synced $totalSynced stock items successfully."
            ];
        } catch (\Exception $e) {
            $this->error("❌ Failed syncing technician {$technician->id}: " . $e->getMessage());

            return [
                'status' => 'failed',
                'technician_id' => $technician->id,
                'message' => "Failed syncing technician: " . $e->getMessage()
            ];
        }
    }
    public function error($message, $code = 500)
    {
        return response()->json([
            'status' => 'failed',
            'message' => $message
        ], $code);
    }

    protected function syncTechnicianAppointments($techId): int
    {
        $currentPage = 1;
        $pageSize = 70;
        $totalSynced = 0;
        $fromDate = now()->subDay()->format('Y-m-d');
        $toDate   = now()->addDay()->format('Y-m-d');
        while (true) {
            $response = $this->dyService->getTechnicianAppointments(payload: [
                'worker' => $techId,
                'currentPage' => $currentPage,
                'pageSize'    => $pageSize,
                'fromDate'    => $fromDate,
                'toDate'      => $toDate,
            ]);
            $appointments = $response['Data']['Appointments'] ?? [];
            if (empty($appointments) || !is_array($appointments)) {
                break;
            }
            // dd($appointments);
            $this->appointmentRepo->newSyncAllTechnicianAppointments($appointments);
            $totalSynced += count($appointments);
            if (count($appointments) < $pageSize) {
                break;
            }
            $currentPage++;
        }
        return $totalSynced;
    }

    protected function syncTechnicianTransfers(User $technician): int
    {
        if (!$technician->technician_rec_id) {
            $this->warn("Technician ID {$technician->id} missing 'technician_rec_id'. Skipping...");
            return 0;
        }

        $payload = [
            'worker' => $technician->technician_rec_id,
        ];

        $response = $this->dyService->getTechnicianTransfers($payload);

        $transferOrders = $response['Data']['TransferOrders'] ?? [];

        if (!empty($transferOrders) && is_array($transferOrders)) {

            $this->transferOrderRepo->syncTransferOrder($transferOrders, $technician->id);
            return count($transferOrders); // return number of transfers
        } else {
            return 0;
        }
    }
}
