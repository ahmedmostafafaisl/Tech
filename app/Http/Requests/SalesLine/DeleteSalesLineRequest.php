<?php

namespace App\Http\Requests\SalesLine;

use App\Models\AppointmentBundle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DeleteSalesLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appointment'    => 'required|integer',
            'book_id'        => 'required|string|max:255',
            'tech_id'        => 'nullable|integer',
            'bundle_id'      => 'nullable|string|max:255',
            'salesLines'     => 'required|array|min:1',
            'salesLines.*'   => 'required|integer',
            'item_numbers'   => 'required|array|min:1|size:' . count((array) $this->input('salesLines', [])),
            'item_numbers.*' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'item_numbers.size' => 'item_numbers count must match salesLines count.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $bookId      = $this->input('book_id');
            $bundleId    = $this->input('bundle_id');
            $itemNumbers = array_map('strtolower', (array) $this->input('item_numbers', []));

            if (empty($bookId) || empty($itemNumbers)) return;

            // Load bundles — filter by bundle_id if provided, otherwise all for book_id
            $bundles = AppointmentBundle::query()
                ->with('items')
                ->where('book_id', $bookId)
                ->where('status', 'added')
                ->when(!empty($bundleId), fn($q) => $q->where('bundle_id', $bundleId))
                ->get();

            foreach ($bundles as $bundle) {
                $bundleItemNumbers = $bundle->items
                    ->map(fn($i) => strtolower($i->item_number))
                    ->toArray();

                $matchedCount = count(array_intersect($itemNumbers, $bundleItemNumbers));

                if ($matchedCount > 0 && $matchedCount < count($bundleItemNumbers)) {
                    $missing = array_diff($bundleItemNumbers, $itemNumbers);

                    $validator->errors()->add(
                        'item_numbers',
                        "Bundle [{$bundle->bundle_name}] has {$matchedCount} of " . count($bundleItemNumbers) . " items selected."
                    );
                    $validator->errors()->add(
                        'item_numbers',
                        "You must include all bundle items to delete: " . implode(', ', $missing)
                    );
                }
            }
        });
    }
}
