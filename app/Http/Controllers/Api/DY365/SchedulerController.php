<?php

namespace App\Http\Controllers\Api\DY365;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class SchedulerController extends Controller
{
    public function runAll(): JsonResponse
    {
        $results = [];

        $commands = [
            'dynamics:sync-warehouses',
            'dynamics:sync-categories',
            'dynamics:sync-technicians',
            // 'sync:warehouse-stock',
            // 'technicians:sync-stock',
            // 'sync:technician-transfers',
            // 'sync:technician-status-requests',
            // 'all:sync-appointments',
            // 'technicians:sync-appointments',
            // 'dynamics:sync-customers',
        ];

        foreach ($commands as $command) {
            try {
                Artisan::call($command);
                $results[$command] = 'Executed Successfully';
            } catch (\Exception $e) {
                $results[$command] = 'Error: ' . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Scheduled commands executed.',
            'results' => $results
        ]);
    }
}
