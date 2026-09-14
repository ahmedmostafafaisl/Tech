<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use App\Services\DY365\DyService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CompleteSuccessPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;
    protected $body;


    public function __construct(Appointment $appointment, array $body)
    {
        $this->appointment = $appointment;
        $this->body = $body;
    }


    public function handle(): void
    {
        try {
            $response = app(DyService::class)->completeSuccessPayments($this->body);

            // Convert response to array
            $data = is_array($response)
                ? $response
                : ($response instanceof \Illuminate\Support\Arrayable ? $response->toArray() : []);

            // Always initialize dy_response to pending if null
            if (is_null($this->appointment->dy_response)) {
                $this->appointment->dy_response = ['status' => 'pending'];
            }

            // Update dy_response with latest data from DyService
            $this->appointment->dy_response = $data;

            // If successful, update additional fields
            if (isset($data['Status']) && $data['Status'] === true) {
                $this->appointment->dy_completed = 1;
                $this->appointment->dy365_status = 'Completed';
            }

            $this->appointment->save();
        } catch (\Throwable $e) {
            \Log::error('CompleteSuccessPayments job failed: ' . $e->getMessage());
        }
    }
}
