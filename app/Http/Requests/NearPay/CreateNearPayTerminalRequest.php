<?php

namespace App\Http\Requests\NearPay;

use Illuminate\Foundation\Http\FormRequest;

class CreateNearPayTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route already requires auth:sanctum + role:super_admin|admin
    }

    public function rules(): array
    {
        return [
            // trsm_code is the physical hardware code sent to NearPay's own Create Terminal call —
            // terminal_id and tid are returned BY that call, never supplied by the caller, so neither is
            // validated here at all.
            'trsm_code' => ['required', 'string', 'max:255', 'unique:nearpay_terminals,trsm_code'],
            'name'      => ['required', 'string', 'max:255'],
        ];
    }
}
