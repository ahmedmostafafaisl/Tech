<?php

namespace App\Repositories\Appointment;



use App\Models\Appointment;
use App\Models\AppointmentStatusImage;
use App\Models\AppointmentStatusChange;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Interfaces\AppointmentStatusChangeRepositoryInterface;

class AppointmentStatusChangeRepository implements AppointmentStatusChangeRepositoryInterface
{
    public function store(array $data)
    {
        $appointment = Appointment::findOrFail($data['appointment_id']);
        $data['status'] = $appointment->status;
        $statusChange = AppointmentStatusChange::create($data);

        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {
                $path = $image->store('appointment_status_images', 'public');
                AppointmentStatusImage::create([
                    'status_change_id' => $statusChange->id,
                    'image' => $path
                ]);
            }
        }

        if ($data['status'] === 'complete') {
            $appointment->update(['status' => 'completed']);

            if (isset($data['items']) && is_array($data['items'])) {
                $appointment->items()->delete(); // Remove existing items before updating

                foreach ($data['items'] as $item) {
                    if (isset($item['image']) && $item['image']->isValid()) {
                        $item['image'] = $item['image']->store('appointment_items', 'public');
                    }
                    $appointment->items()->create($item);
                }
            }

            if (isset($data['parts']) && is_array($data['parts'])) {
                $appointment->parts()->delete(); // Remove old parts before updating

                foreach ($data['parts'] as $part) {
                    if (isset($part['image']) && $part['image']->isValid()) {
                        $part['image'] = $part['image']->store('appointment_parts', 'public');
                    }
                    $appointment->parts()->create($part);
                }
            }
        }

        return $appointment->load(['items', 'parts']);
    }

    public function update($id, array $data)
    {
        $statusChange = AppointmentStatusChange::findOrFail($id);
        $statusChange->update($data);

        if (!empty($data['images'])) {
            foreach ($data['images'] as $image) {
                $path = $image->store('appointment_status_images', 'public');
                AppointmentStatusImage::create([
                    'status_change_id' => $statusChange->id,
                    'image' => $path
                ]);
            }
        }

        return $statusChange;
    }

    public function getAll()
    {
        return AppointmentStatusChange::with('images')->get();
    }

    public function getById($id)
    {
        return AppointmentStatusChange::with('images')->findOrFail($id);
    }

    public function delete($id)
    {
        $statusChange = AppointmentStatusChange::findOrFail($id);

        // Delete associated images
        foreach ($statusChange->images as $image) {
            Storage::delete('public/' . $image->image_path);
            $image->delete();
        }

        return $statusChange->delete();
    }
}
