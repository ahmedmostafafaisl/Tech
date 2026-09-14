<?php

namespace App\Repositories\Emergency;

use App\Models\Appointment;
use App\Models\AppointmentLine;
use App\Helper\ApiResponseHelper;
use App\Models\EmergencyMainItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\EmergencyItemCondition;
use App\Models\AppointmentLineCondition;
use App\Models\EmergencyItemConditionImage;
use App\Models\AppointmentLineConditionImage;
use App\Http\Resources\Emergency\EmergencyItemConditionResource;
use App\Repositories\Interfaces\EmergencyItemConditionRepositoryInterface;


class EmergencyItemConditionRepository implements EmergencyItemConditionRepositoryInterface
{

    use ApiResponseHelper;
    public function store(array $data)
    {
        // return $data['status'];
        // $item = EmergencyMainItem::findOrFail($data['emergency_main_item_id']);
        $line = AppointmentLine::findOrFail($data['appointment_line_id']);
        $appointment = Appointment::findOrFail($line->appointment_id);
        $authUser = Auth::user();
        if ($appointment->type !== 'emergency') {
            return $this->setCode(404)
                ->setData([])
                ->setMessage('Invalid operation: Appointment type must be emergency.')
                ->send();
        }
        // Authorization: check tech and type
        if ($appointment->technician_id !== $authUser->id &&   !$authUser->hasPermissionTo('create emergency_item_conditions')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to create this appointment item conditions.')
                ->send();
        }
        $line->update([
            'item_form_type' => $data['status'],
        ]);

        return DB::transaction(function () use ($data) {
            $condition = AppointmentLineCondition::create([
                'appointment_line_id' => $data['appointment_line_id'],
                'status' => $data['status'],
            ]);


            $this->storeOrUpdateSideData($condition, $data);
            $condition = $condition->load('images');

            return $this->setCode(code: 200)->setData(new EmergencyItemConditionResource($condition))->setMessage('Success.')->send();
        });
    }


    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $condition = AppointmentLineCondition::findOrFail($id);

            $condition->update([
                'status' => $data['status'],
            ]);

            // Delete all related images (fresh replace pattern)
            $condition->images()->delete();

            $this->storeOrUpdateSideData($condition, $data);
            $condition = $condition->load('images');
            return $this->setCode(code: 200)->setData(new EmergencyItemConditionResource($condition))->setMessage('Success.')->send();
        });
    }

    private function storeOrUpdateSideData(AppointmentLineCondition $condition, array $data)
    {
        $sides = ['right', 'left', 'front', 'back', 'top'];

        foreach ($sides as $side) {
            $key = "{$side}_side_conditon";
            $sideData = $data[$key] ?? [];

            $condition->update([
                "{$side}_side_have_issue" => $sideData['have_issue'] ?? false,
                "{$side}_side_issues" => $sideData['issues'] ?? [],
                "{$side}_side_issue_note" => $sideData['issue_note'] ?? null,
            ]);

            if (!empty($sideData['images'])) {
                foreach ($sideData['images'] as $image) {
                    $path = $image->store('emergency_conditions', 's3');
                    AppointmentLineConditionImage::create([
                        'condition_id' => $condition->id,
                        'side' => $side,
                        'path' => $path,
                    ]);
                }
            }
        }
    }

    public function getByMainItemId(int $mainItemId)
    {
        $condition = AppointmentLineCondition::with('images')
            ->where('appointment_line_id', $mainItemId)
            ->first();

        return $this->setCode(code: 200)->setData(new EmergencyItemConditionResource($condition))->setMessage('Success.')->send();
    }

    private function storeImages(AppointmentLineCondition $condition, array $imageData): void
    {
        foreach ($imageData['files'] as $file) {
            $path = $file->store('emergency_conditions', 's3');
            AppointmentLineConditionImage::create([
                'condition_id' => $condition->id,
                'side' => $imageData['side'],
                'path' => $path,
            ]);
        }
    }

    public function getConditionsByAppointment(int $appointmentId)
    {
        $conditions = AppointmentLineCondition::with(['images', 'mainItem'])
            ->whereHas('mainItem', function ($query) use ($appointmentId) {
                $query->where('appointment_id', $appointmentId);
            })
            ->get();
        return $this->setCode(code: 200)->setData(EmergencyItemConditionResource::collection($conditions))->setMessage('Success.')->send();
    }

    public function deleteByMainItem(int $emergencyMainItemId): bool
    {
        // return $data['status'];
        $item = EmergencyMainItem::findOrFail($emergencyMainItemId);
        $appointment = Appointment::findOrFail($item->appointment_id);
        $authUser = Auth::user();
        if ($appointment->type !== 'emergency') {
            return false;
        }
        // Authorization: check tech and type
        if ($appointment->technician_id !== $authUser->id &&   !$authUser->hasPermissionTo('create emergency_item_conditions')) {
            return false;
        }
        $conditions = EmergencyItemCondition::with('images')
            ->where('emergency_main_item_id', $emergencyMainItemId)
            ->get();

        $mainItem = EmergencyMainItem::findOrFail($emergencyMainItemId);



        $mainItem->update([
            'item_form_type' => null,
            'item_form_status' => 'pending',
        ]);
        // dd($mainItem->toArray());
        $mainItem->save();
        // If no conditions found, return false

        if ($conditions->isEmpty()) {
            return false;
        }

        foreach ($conditions as $condition) {
            // Delete associated images
            foreach ($condition->images as $image) {
                // Optional: Delete physical file if needed
                // Storage::delete($image->path);
                $image->delete();
            }

            $condition->delete();
        }

        return true;
    }
}
