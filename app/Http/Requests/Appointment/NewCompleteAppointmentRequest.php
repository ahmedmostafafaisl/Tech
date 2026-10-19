<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class NewCompleteAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->tech_id)) {
            $this->merge(['tech_id' => auth()->user()?->tech_id]);
        }
    }

    public function rules(): array
    {
        return [
            'sales_order_id'               => 'required|string',
            'book_id'                      => 'required|string',
            'tech_id'                      => 'nullable|integer',
            'discount'                     => 'nullable|numeric|min:0',
            'total_price'                  => 'nullable|numeric|min:0',
            'customer_phone'               => 'nullable|string|min:8|max:20',
            'order_type'                   => 'required|string',

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
                        "Number of serials must equal quantity ($quantity) for item {$item['ItemNumber']}."
                    );
                }
            }

            // ── 2) InstallmentStatus rules for fes-tech-visit / fes-transportation ────────
            $items = $this->input('items', []);
            $installmentStatus = $this->input('InstallmentStatus');

            $hasFesTechVisit = collect($items)->contains(
                fn($item) => strtolower(trim($item['ItemNumber'] ?? '')) === 'fes-tech-visit'
            );
            $hasDlvFee1 = collect($items)->contains(
                fn($item) => strtolower(trim($item['ItemNumber'] ?? '')) === 'fes-transportation'
            );

            if ($installmentStatus === 'Need_installation') {
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
            if ($hasFesTechVisit && $hasDlvFee1) {
                $validator->errors()->add(
                    'items',
                    'combination_validation(fes-tech-visit,fes-transportation)'
                );
            }

            // ── 3) Serial reuse check ──────────────────────────────────────────
            // Same shared, permanent rule the appointment API uses to decide available_serials (see
            // PermanentlyTransmittedSerials): a serial is rejected here only if it's confirmed sent to
            // DY365 under a DIFFERENT appointment with no ChangeRequest against it. Previously this
            // duplicated its own, different, time-windowed "same technician, picked today" check — which
            // could reject a serial the appointment API was still showing as selectable, since neither
            // definition agreed with the other.
            if ($bookId) {
                $itemNumbers = collect($this->input('items', []))
                    ->pluck('ItemNumber')
                    ->filter()
                    ->map(fn($i) => strtolower(trim($i)))
                    ->unique()
                    ->values()
                    ->all();

                $reservedByItem = \App\Services\Serials\PermanentlyTransmittedSerials::reservedBookIdsByItem($bookId, $itemNumbers);

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
            'items.required'                   => 'At least one item is required.',
            'items.*.SaleslineId.required'     => 'SaleslineId is required for each item.',
            'items.*.ItemNumber.required'      => 'ItemNumber is required for each item.',
            'items.*.Quantity.required'        => 'Quantity is required for each item.',
            'items.*.Quantity.min'             => 'Quantity must be at least 1.',
            'items.*.orderTypeRecId.required'  => 'orderTypeRecId is required for each item.',
            'items.*.WarrantyStatus.in'        => 'WarrantyStatus must be Yes, No, or None.',
            'InstallmentStatus.required_if'    => 'InstallmentStatus is required for تركيب order types.',
            'InstallmentStatus.in'             => 'InstallmentStatus must be Completed or Need_installation.',
        ];
    }
}
