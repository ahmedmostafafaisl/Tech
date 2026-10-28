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
                        'NFC'                     => 'NFC',
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
            'payments.*.payment_type'      => 'required|string|in:TABI,TAMARA,E-Commerce,CASH,POS,TRNS,NFC',
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

            // ── 4) Serial reuse check ──────────────────────────────────────────
            // Same shared, permanent rule the appointment API uses to decide available_serials (see
            // PermanentlyTransmittedSerials): a serial is rejected here only if it's confirmed sent to
            // DY365 under a DIFFERENT appointment with no ChangeRequest against it. Previously this
            // duplicated its own, different, time-windowed "same technician, picked today" check — which
            // could reject a serial the appointment API was still showing as selectable, since neither
            // definition agreed with the other.
            //
            // Whole check is skippable via settings key serial_reuse_validation_active (default: ON — this
            // is an existing safety check, so a missing setting row must never silently disable it). Off
            // means every submitted serial is accepted here regardless of reservation status, with no
            // PermanentlyTransmittedSerials or ReservedSerialLiveVerifier call made at all — distinct from
            // serial_live_cancellation_check_active, which only controls the live-release behavior WITHIN
            // this same check, not whether the check itself runs.
            if ($bookId && \App\Models\Setting::isActive('serial_reuse_validation_active', true)) {
                $itemNumbers = collect($this->input('items', []))
                    ->pluck('ItemNumber')
                    ->filter()
                    ->map(fn($i) => strtolower(trim($i)))
                    ->unique()
                    ->values()
                    ->all();

                $reservedByItem = \App\Services\Serials\PermanentlyTransmittedSerials::reservedBookIdsByItem($bookId, $itemNumbers);

                // Submission time only (never the browsing endpoints) — for the handful of serials that
                // would otherwise be rejected here, checks live whether the reserving appointment has since
                // been cancelled in DY365 with no ChangeRequest ever recorded for it, and releases it on the
                // spot if so. Off unless explicitly enabled (settings key
                // serial_live_cancellation_check_active) — see ReservedSerialLiveVerifier.
                $reservedByItem = \App\Services\Serials\ReservedSerialLiveVerifier::releaseCancelledReservations($reservedByItem);

                foreach ($this->input('items', []) as $index => $item) {
                    $serials = $item['serials'] ?? [];

                    if (empty($serials)) {
                        continue;
                    }

                    $itemNumber = strtolower(trim($item['ItemNumber'] ?? ''));
                    $reservedSerialMap = $reservedByItem[$itemNumber] ?? [];

                    if (empty($reservedSerialMap)) {
                        continue;
                    }

                    foreach ($serials as $serialIndex => $serial) {
                        $reservedBookId = $reservedSerialMap[\App\Services\Serials\PermanentlyTransmittedSerials::cleanSerial($serial)] ?? null;

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
            'payments.*.payment_type.in'       => 'Payment type must be TABI, TAMARA, E-Commerce, CASH, POS, TRNS, or NFC.',
            'payments.*.phone.required'        => 'Phone is required for each payment.',
            'payments.*.reference_id.max'      => 'Reference ID must not exceed 255 characters.',
        ];
    }
}
