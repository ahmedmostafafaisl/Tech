<?php
// ===== AddSalesLineRequest.php =====

namespace App\Http\Requests\SalesLine;

use Illuminate\Foundation\Http\FormRequest;

class AddSalesLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment'                  => 'required|integer',
            'book_id'                      => 'required|string|max:255',
            'tech_id'                      => 'nullable|integer',
            'items'                        => 'required|array|min:1',
            'items.*.orderTypeRecId'       => 'required',
            'items.*.ItemNumber'           => 'required|string',
            'items.*.Quantity'             => 'required|numeric|min:1',
            'items.*.WarrantyStatus'       => 'nullable|in:Yes,No,None',
            'items.*.PaymentMethod'        => 'nullable|string',
            'items.*.serials'              => 'nullable|array',
            'items.*.serials.*'            => 'required|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
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
        });
    }

    public function messages(): array
    {
        return [
            'items.*.WarrantyStatus.in' => 'Warranty status must be Yes, No, or None.',
        ];
    }
}
