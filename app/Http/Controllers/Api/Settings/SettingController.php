<?php

namespace App\Http\Controllers\Api\Settings;

use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    private const TECHNICIAN_TO_TECHNICIAN_KEY = 'transfer_technician_to_technician_active';

    public function index()
    {
        return response()->json([
            'appointment_cooldown_minutes' => (int) Setting::get('appointment_cooldown_minutes', 15),
        ]);
    }

    public function updateCooldown(Request $request)
    {
        $request->validate([
            'value' => 'required|numeric|min:0',
        ]);

        Setting::where('key', 'appointment_cooldown_minutes')
            ->update(['value' => $request->value]);

        return response()->json([
            'message' => 'Cooldown updated successfully.',
            'appointment_cooldown_minutes' => (int) $request->value,
        ]);
    }



    // ===== NEW: list every setting (public) =====
    public function indexAll()
    {
        return response()->json(
            \App\Models\Setting::pluck('value', 'key')
        );
    }


    // ===== NEW: get one setting by key (public) =====
    public function show(string $key)
    {
        $setting = \App\Models\Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json(['status' => true, 'data' => $setting]);
    }

    // ===== NEW: create a setting (protected) =====
    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'key'   => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('settings', 'key')],
            'value' => ['required', 'string'],
        ]);

        $setting = \App\Models\Setting::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Created successfully.',
            'data'    => $setting,
        ], 201);
    }

    // ===== NEW: update a setting by key (protected) =====
    public function update(\Illuminate\Http\Request $request, string $key)
    {
        $setting = \App\Models\Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'value' => ['required', 'string'],
        ]);

        $setting->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Updated successfully.',
            'data'    => $setting->fresh(),
        ]);
    }

    // ===== NEW: delete a setting by key (protected) =====
    public function destroy(string $key)
    {
        $setting = \App\Models\Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        $setting->delete();

        return response()->json(['status' => true, 'message' => 'Deleted successfully.']);
    }


    // ===== NEW: check if technician-to-technician transfer is active (public) =====
    public function technicianToTechnicianStatus()
    {
        return response()->json([
            'status' => true,
            'data'   => [
                'type'   => 'TechnicianToTechnician',
                'active' => Setting::isActive(self::TECHNICIAN_TO_TECHNICIAN_KEY),
            ],
        ]);
    }

    public function updateTechnicianToTechnicianStatus(Request $request)
    {

        $validated = $request->validate([
            'active' => 'required|boolean',
        ]);

        Setting::updateOrCreate(
            ['key' => self::TECHNICIAN_TO_TECHNICIAN_KEY],
            ['value' => $validated['active'] ? '1' : '0']
        );

        return response()->json([
            'status'  => true,
            'message' => 'Updated successfully.',
            'data'    => [
                'type'   => 'TechnicianToTechnician',
                'active' => $validated['active'],
            ],
        ]);
    }
}
