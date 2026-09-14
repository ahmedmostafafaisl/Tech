<?php

namespace App\Http\Controllers\Api\Service;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Imports\ServicesImport;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class ServiceController extends Controller
{
    protected $dyService;
    public function __construct(DyService $dyService)
    {
        $this->dyService = $dyService;
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        Excel::import(new ServicesImport, $request->file('file'));

        return response()->json(['message' => '✅ Services imported successfully!'], 200);
    }


    public function index(Request $request)
    {
        $request->validate([
            'bookId' => 'nullable|string|max:100',
        ]);

        // Get all services first
        $services = Service::all()->map(function ($service) {
            $service->item_number = strtolower($service->item_number);
            return $service;
        });

        // If bookId exists → filter out matched services
        if (!empty($request->bookId)) {

            // 1. Fetch appointment
            $appointmentResponse = $this->dyService->getAppointmentByBookId($request->bookId);
            $appointmentData = $appointmentResponse['Data'] ?? [];
            $salesLines = $appointmentData['SalesLines'] ?? [];

            // 2. Extract lowercase ItemNumbers from SalesLines
            $salesLineItems = collect($salesLines)
                ->pluck('ItemNumber')
                ->map(fn($item) => strtolower($item))
                ->toArray();

            // 3. Filter out services whose item_number matches SalesLines
            $services = $services->reject(function ($service) use ($salesLineItems) {
                return in_array($service->item_number, $salesLineItems);
            })->values();
        }

        return response()->json($services);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'item_number' => 'nullable|string|max:100',
            'main_type' => 'nullable|string|max:100',
            'sub_type' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
        ]);

        $service = Service::create($request->all());

        return response()->json(['message' => '✅ Service created successfully!', 'service' => $service], 201);
    }
    public function show($id)
    {
        $service = Service::find($id);
        if ($service) {
            return response()->json($service);
        }
        return response()->json(['message' => '❌ Service not found!'], 404);
    }
    public function update(Request $request, $id)
    {
        $service = Service::find($id);
        if (!$service) {
            return response()->json(['message' => '❌ Service not found!'], 404);
        }

        $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'item_number' => 'sometimes|nullable|string|max:100',
            'main_type' => 'sometimes|nullable|string|max:100',
            'sub_type' => 'sometimes|nullable|string|max:100',
            'price' => 'sometimes|nullable|numeric|min:0',
        ]);

        $service->update($request->all());

        return response()->json(['message' => '✅ Service updated successfully!', 'service' => $service], 200);
    }


    public function delete($id)
    {
        $service = Service::find($id);
        if ($service) {
            $service->delete();
            return response()->json(['message' => '✅ Service deleted successfully!'], 200);
        }
        return response()->json(['message' => '❌ Service not found!'], 404);
    }
}
