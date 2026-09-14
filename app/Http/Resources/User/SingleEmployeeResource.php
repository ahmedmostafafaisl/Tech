<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleEmployeeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $summary = [
            'appointment_inprogress' => 0,
            'appointment_finished' => 0,
            'appointment_not_started' => 0,
        ];
        $type = $this->type ?? null;

        if ($type === 'customer') {
            $summary = $this->calculateAppointmentSummary($this->customerAppointments);
        } elseif ($type === 'tech') {
            $summary = $this->calculateTechSummary();
        }

        return [
            'id' => $this->id,
            'user_name' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'type' => $this->type ?? null,
            'role' => $this->getRoleNames()->first(),
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'summary' => $summary,
            'permissions' => $this->groupedPermissions(),
        ];
    }

    protected function calculateTechSummary(): array
    {
        return [
            'total_appointment' => $this->technicianAppointments->count(),
            'total_items' => $this->technicianAppointments->sum(fn($appointment) => $appointment->items->count()),
            'total_tasks' => $this->tasks->count(), // 👈 user’s direct tasks
            'appointments_completed' => $this->technicianAppointments->where('status', 'complete')->count(),
        ];
    }

    protected function calculateAppointmentSummary($appointments)
    {
        return [
            'appointment_inprogress' => $appointments->whereIn('status', ['on_way', 'on_site', 'hold'])->count(),
            'appointment_finished' => $appointments->where('status', 'complete')->count(),
            'appointment_not_started' => $appointments->whereIn('status', ['pending', 'reschedule'])->count(),
        ];
    }

    public function groupedPermissions()
    {
        return $this->permissions->groupBy(function ($permission) {
            return explode(' ', $permission->name, 2)[1] ?? 'other';
        })->map(function ($group) {
            return $group->pluck('name')->values();
        });
    }
}
