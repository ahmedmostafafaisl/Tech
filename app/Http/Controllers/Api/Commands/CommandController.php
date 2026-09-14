<?php

namespace App\Http\Controllers\Api\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CommandController extends Controller
{
    public function syncWarehouses()
    {
        Artisan::call('dynamics:sync-warehouses');
        return $this->response('dynamics:sync-warehouses');
    }

    public function syncCategories()
    {
        Artisan::call('dynamics:sync-categories');
        return $this->response('dynamics:sync-categories');
    }

    public function syncTechnicians()
    {
        Artisan::call('dynamics:sync-technicians');
        return $this->response('dynamics:sync-technicians');
    }

    public function syncStock()
    {
        Artisan::call('technicians:sync-stock');
        return $this->response('technicians:sync-stock');
    }

    public function syncWarehouseStock()
    {
        Artisan::call('sync:warehouse-stock');
        return $this->response('sync:warehouse-stock');
    }

    public function syncTechnicianTransfers()
    {
        Artisan::call('sync:technician-transfers');
        return $this->response('sync:technician-transfers');
    }

    public function syncTechnicianAppointments()
    {
        Artisan::call('technicians:sync-appointments');
        return $this->response('technicians:sync-appointments');
    }

    public function syncAllAppointments()
    {
        Artisan::call('all:sync-appointments');
        return $this->response('all:sync-appointments');
    }

    public function syncTechnicianStatusRequests()
    {
        Artisan::call('sync:technician-status-requests');
        return $this->response('sync:technician-status-requests');
    }

    public function syncCustomers()
    {
        Artisan::call('dynamics:sync-customers');
        return $this->response('dynamics:sync-customers');
    }

    /**
     * Format JSON response
     */
    private function response($command)
    {
        return response()->json([
            'status'  => 'success',
            'command' => $command,
            'output'  => Artisan::output()
        ]);
    }
}
