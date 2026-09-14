<?php

namespace App\Http\Requests\SalesLine;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $salesLines = $this->input('salesLines', []);

        if (is_array($salesLines)) {
            $salesLines = array_map(function ($line) {
                if (!is_array($line)) {
                    return $line;
                }

                // ✅ Normalize warrantyStatus → WarrantyStatus
                if (array_key_exists('warrantyStatus', $line) && !array_key_exists('WarrantyStatus', $line)) {
                    $line['WarrantyStatus'] = $line['warrantyStatus'];
                }

                // ✅ Normalize paymentMethod → PaymentMethod
                if (array_key_exists('paymentMethod', $line) && !array_key_exists('PaymentMethod', $line)) {
                    $line['PaymentMethod'] = $line['paymentMethod'];
                }

                return $line;
            }, $salesLines);

            $this->merge(['salesLines' => $salesLines]);
        }
    }

    public function rules(): array
    {
        return [
            'appointment'                  => 'required|integer',
            'book_id'                      => 'required|string|max:255',
            'tech_id'                      => 'nullable|integer',
            'salesLines'                   => 'required|array|min:1',
            'salesLines.*.salesLineRecId'  => 'required|integer',
            'salesLines.*.itemNumber'      => 'required|string|max:255',
            'salesLines.*.quantity'        => 'required|numeric|min:1',
            'salesLines.*.WarrantyStatus'  => 'nullable|in:Yes,No,None',
            'salesLines.*.PaymentMethod'   => 'nullable|string',
            'salesLines.*.IsPaid' => 'nullable|boolean',
            'salesLines.*.serials'         => 'nullable|array',
            'salesLines.*.serials.*'       => 'required|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('salesLines', []) as $index => $line) {
                $serials  = $line['serials'] ?? null;
                $quantity = (int) ($line['quantity'] ?? 0);

                if (!empty($serials) && count($serials) !== $quantity) {
                    $validator->errors()->add(
                        "salesLines.$index.serials",
                        "Number of serials must equal quantity ($quantity) for salesLine {$line['salesLineRecId']}."
                    );
                }
            }
        });
    }
}
