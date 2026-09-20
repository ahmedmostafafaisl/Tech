<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DirectAppointmentPayment;

class SendPaymentLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->tech_id)) {
            $this->merge([
                'tech_id' => auth()->user()?->tech_id ?? auth()->user()?->technician_rec_id,
            ]);
        }

        $payments = collect($this->input('payments', []))
            ->map(function ($payment) {
                if (!is_array($payment)) {
                    return $payment;
                }

                if (isset($payment['payment_type'])) {
                    $rawType   = (string) $payment['payment_type'];
                    $typeUpper = strtoupper(trim($rawType));

                    $payment['payment_type'] = match ($typeUpper) {
                        'TABBY', 'TABI'           => 'TABI',
                        'TAMARA'                  => 'TAMARA',
                        'E-COMMERCE', 'ECOMMERCE' => 'E-Commerce',
                        'CASH'                    => 'CASH',
                        'POS'                     => 'POS',
                        'TRNS'                    => 'TRNS',
                        default                   => $rawType,
                    };
                }

                return $payment;
            })
            ->values()
            ->toArray();

        $this->merge([
            'discount' => $this->input('discount', 0),
            'payments' => $payments,
        ]);
    }

    public function rules(): array
    {
        return [
            'sales_order_id'               => 'required|string',
            'book_id'                      => 'required|string',
            'customer_phone'               => 'nullable|string|min:8|max:20',
            'order_type'                   => 'required|string',
            'tech_id'                      => 'nullable|integer',
            'discount'                     => 'nullable|numeric|min:0',
            'total_price'                  => 'required|numeric|min:0',

            // Only required when order_type is تركيب (installation).
            'InstallmentStatus'            => 'required_if:order_type,تركيب|nullable|string|in:Completed,Need_installation',

            'items'                        => 'nullable|array',
            'items.*.SaleslineId'          => 'required|integer',
            'items.*.ItemNumber'           => 'required|string',
            'items.*.Quantity'             => 'required|integer|min:1',
            'items.*.orderTypeRecId'       => 'required|integer',
            'items.*.WarrantyStatus'       => 'nullable|in:Yes,No,None',
            'items.*.PaymentMethod'        => 'nullable|string',
            'items.*.max_quantity'         => 'nullable|integer|min:0',
            'items.*.serials'              => 'nullable|array',
            'items.*.serials.*'            => 'required|string',

            'payments'                     => 'required|array|min:1',
            'payments.*.price'             => 'required|numeric|min:0.01',
            'payments.*.payment_type'      => 'required|string|in:TABI,TAMARA,E-Commerce,CASH,POS,TRNS',
            'payments.*.phone'             => 'required|string|min:8|max:20',
            'payments.*.reference_id'      => 'nullable|string|max:255',
            'payments.*.is_paid'           => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bookId = $this->input('book_id');

            // ── 1) Serial count check (existing logic) ──────────────────────────
            foreach ($this->input('items', []) as $index => $item) {
                $serials  = $item['serials'] ?? null;
                $quantity = (int) ($item['Quantity'] ?? 0);

                if (!empty($serials) && count($serials) !== $quantity) {
                    $validator->errors()->add(
                        "items.$index.serials",
                        "Number of serials must equal quantity ({$quantity}) for item {$item['ItemNumber']}."
                    );
                }
            }

            // ── 2) InstallmentStatus rules for fes-tech-visit / fes-transportation ────────
            $items = $this->input('items', []);
            $installmentStatus = $this->input('InstallmentStatus');

            $hasFesTechVisit = collect($items)->contains(
                fn($item) => strtolower(trim($item['ItemNumber'] ?? '')) === 'fes-tech-visit'
            );
            $hasFesTransportation = collect($items)->contains(
                fn($item) => strtolower(trim($item['ItemNumber'] ?? '')) === 'fes-transportation'
            );

            if ($installmentStatus === 'Need_installation') {
                // Must be exactly one item, and that item must be
                // fes-tech-visit — covers both "if only 1 item, must be
                // fes-tech-visit" and "fes-tech-visit cannot be combined
                // with other products" in one check.
                if (count($items) !== 1 || strtolower(trim($items[0]['ItemNumber'] ?? '')) !== 'fes-tech-visit') {
                    $validator->errors()->add(
                        'items',
                        'need_installation_validation(fes-tech-visit)'
                    );
                }
            } elseif ($installmentStatus === 'Completed') {
                if ($hasFesTechVisit) {
                    $validator->errors()->add(
                        'items',
                        'completed_validation(fes-tech-visit)'
                    );
                }
            }

            // fes-tech-visit and fes-transportation can never appear together,
            // regardless of InstallmentStatus.
            if ($hasFesTechVisit && $hasFesTransportation) {
                $validator->errors()->add(
                    'items',
                    'combination_validation(fes-tech-visit,fes-transportation)'
                );
            }

            // ── 3) Duplicate reference_id per (book_id + payment_type) check ────
            if (!$bookId) {
                return;
            }

            // Also catch duplicates within the same request
            // e.g. sending TABI 805328 twice in one call
            $seenInRequest = [];

            foreach ($this->input('payments', []) as $index => $payment) {
                $referenceId = $payment['reference_id'] ?? null;
                $paymentType = $payment['payment_type'] ?? null;

                // Skip if no reference_id — auto-generated ones are always unique
                if (!$referenceId || !$paymentType) {
                    continue;
                }

                // ── 2a) Duplicate within the same request ──────────────────────
                $requestKey = $paymentType . '|' . $referenceId;

                if (isset($seenInRequest[$requestKey])) {
                    $validator->errors()->add(
                        "payments.$index.reference_id",
                        "Duplicate in request: reference_id '{$referenceId}' already used for payment type '{$paymentType}' in this submission."
                    );
                    continue;
                }

                $seenInRequest[$requestKey] = true;

                // ── 2b) Duplicate against existing DB records ──────────────────
                $exists = DirectAppointmentPayment::where('book_id', $bookId)
                    ->where('payment_type', $paymentType)
                    ->where('reference_id', $referenceId)
                    ->where('status', '!=', 'failed') // allow retry of failed payments
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        "payments.$index.reference_id",
                        "Reference ID '{$referenceId}' has already been used for payment type '{$paymentType}' on booking '{$bookId}'."
                    );
                }
            }

            // ── 4) Same-technician serial reuse check ────────────────────────────
            $techId = $this->input('tech_id');

            if ($techId && $bookId) {
                $excludedBookIds = \App\Models\ChangeRequest::query()
                    ->where('tech_id', $techId)
                    ->whereBetween('created_at', [today()->subDay()->startOfDay(), today()->endOfDay()])
                    ->pluck('book_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();

                $cleanSerial = fn($s) => preg_replace(
                    '/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u',
                    '',
                    (string) $s
                );

                $reservedRows = \App\Models\AppointmentTransactionSerial::query()
                    ->select(
                        'appointment_transaction_serials.item_number',
                        'appointment_transaction_serials.serial',
                        'appointment_transactions.book_id'
                    )
                    ->join(
                        'appointment_transaction_lines',
                        'appointment_transaction_lines.id',
                        '=',
                        'appointment_transaction_serials.appointment_transaction_line_id'
                    )
                    ->join(
                        'appointment_transactions',
                        'appointment_transactions.id',
                        '=',
                        'appointment_transaction_lines.appointment_transaction_id'
                    )
                    ->where('appointment_transactions.tech_id', $techId)
                    ->where('appointment_transactions.book_id', '!=', $bookId)
                    ->whereBetween('appointment_transactions.created_at', [today()->subDay()->startOfDay(), today()->endOfDay()])
                    ->when(!empty($excludedBookIds), function ($query) use ($excludedBookIds) {
                        $query->whereNotIn('appointment_transactions.book_id', $excludedBookIds);
                    })
                    ->get();

                // item_number => [ cleaned_serial => book_id ]
                $reservedByItem = $reservedRows
                    ->groupBy(fn($row) => strtolower(trim($row->item_number)))
                    ->map(function ($rows) use ($cleanSerial) {
                        return $rows->mapWithKeys(fn($row) => [$cleanSerial($row->serial) => $row->book_id]);
                    });

                foreach ($this->input('items', []) as $index => $item) {
                    $serials = $item['serials'] ?? [];

                    if (empty($serials)) {
                        continue;
                    }

                    $itemNumber = strtolower(trim($item['ItemNumber'] ?? ''));
                    $reservedSerialMap = $reservedByItem->get($itemNumber, collect());

                    if ($reservedSerialMap->isEmpty()) {
                        continue;
                    }

                    foreach ($serials as $serialIndex => $serial) {
                        $reservedBookId = $reservedSerialMap->get($cleanSerial($serial));

                        if ($reservedBookId !== null) {
                            $validator->errors()->add(
                                "items.$index.serials.$serialIndex",
                                "Serial '{$serial}' -- used on book id = {$reservedBookId}"
                            );
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sales_order_id.required'          => 'Sales order ID is required.',
            'book_id.required'                 => 'Book ID is required.',
            'total_price.required'             => 'Total price is required.',
            'total_price.numeric'              => 'Total price must be numeric.',
            'discount.numeric'                 => 'Discount must be numeric.',

            'InstallmentStatus.required_if'    => 'InstallmentStatus is required for تركيب order types.',
            'InstallmentStatus.in'             => 'InstallmentStatus must be Completed or Need_installation.',

            'items.*.SaleslineId.required'     => 'SaleslineId is required for each item.',
            'items.*.ItemNumber.required'      => 'ItemNumber is required for each item.',
            'items.*.Quantity.required'        => 'Quantity is required for each item.',
            'items.*.Quantity.min'             => 'Quantity must be at least 1.',
            'items.*.orderTypeRecId.required'  => 'orderTypeRecId is required for each item.',
            'items.*.WarrantyStatus.in'        => 'WarrantyStatus must be Yes, No, or None.',

            'payments.required'                => 'At least one payment is required.',
            'payments.array'                   => 'Payments must be an array.',
            'payments.min'                     => 'At least one payment is required.',
            'payments.*.price.required'        => 'Payment price is required.',
            'payments.*.price.min'             => 'Payment price must be greater than 0.',
            'payments.*.payment_type.required' => 'Payment type is required.',
            'payments.*.payment_type.in'       => 'Payment type must be TABI, TAMARA, E-Commerce, CASH, POS, or TRNS.',
            'payments.*.phone.required'        => 'Phone is required for each payment.',
            'payments.*.reference_id.max'      => 'Reference ID must not exceed 255 characters.',
        ];
    }
}
