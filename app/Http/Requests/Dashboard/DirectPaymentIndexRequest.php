<?php

namespace App\Http\Requests\Dashboard;

use App\Services\Payment\DirectPaymentGateways;
use App\Services\Payment\DirectPaymentListing;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class DirectPaymentIndexRequest extends FormRequest
{
    /** Access is enforced by the route middleware (super_admin | admin | the "view payments" permission). */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('type')) {
            $merge['type'] = strtolower(trim((string) $this->input('type')));
        }

        foreach (['reference_id', 'payment_id', 'search'] as $field) {
            if (is_string($this->input($field))) {
                $merge[$field] = trim($this->input($field));
            }
        }

        // the dashboard's own name is currentPage; `page` is accepted as the usual Laravel alias
        if (! $this->has('currentPage') && $this->has('page')) {
            $merge['currentPage'] = $this->input('page');
        }

        $this->merge($merge);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type'         => ['nullable', 'string', Rule::in(DirectPaymentGateways::types())],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'payment_id'   => ['nullable', 'string', 'max:255'],
            'search'       => ['nullable', 'string', 'max:255'],
            'date'         => ['nullable', 'date_format:Y-m-d', 'prohibits:from_date,to_date'],
            'from_date'    => ['nullable', 'date_format:Y-m-d'],
            'to_date'      => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'currentPage'  => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:' . DirectPaymentListing::MAX_PER_PAGE],
        ];
    }

    /** Always a JSON 422, whatever the Accept header says. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => 422,
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
