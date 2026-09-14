<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;

class InvoiceSettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => InvoiceSetting::allAsArray(),
        ]);
    }

    public function show(string $key)
    {
        $setting = InvoiceSetting::where('key', $key)->first();

        if (! $setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'key'   => $setting->key,
                'value' => $setting->parsed_value,
                'type'  => $setting->type,
            ],
        ]);
    }

    public function update(Request $request, string $key)
    {
        $request->validate([
            'value' => 'required',
            'type'  => 'nullable|in:text,json',
        ]);

        $type = $request->input('type', 'text');
        $value = $request->input('value');

        if ($type === 'json' && ! is_array($value)) {
            return response()->json([
                'status' => false,
                'message' => 'value must be array when type is json',
            ], 422);
        }

        $setting = InvoiceSetting::setValue($key, $value, $type);

        return response()->json([
            'status' => true,
            'message' => 'Setting updated successfully',
            'data' => [
                'key'   => $setting->key,
                'value' => $setting->parsed_value,
                'type'  => $setting->type,
            ],
        ]);
    }
}
