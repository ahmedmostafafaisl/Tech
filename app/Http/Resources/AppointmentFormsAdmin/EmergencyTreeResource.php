<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyTreeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'devices' => collect(data_get($this, 'devices', $this->resource))->map(function ($deviceNode) {
                $device = data_get($deviceNode, 'device', $deviceNode);

                return [
                    'device' => [
                        'id' => data_get($device, 'id'),
                        'label_ar' => data_get($device, 'label_ar'),
                        'label_en' => data_get($device, 'label_en'),
                        'sort_order' => data_get($device, 'sort_order', 0),
                        'is_active' => (bool) data_get($device, 'is_active', true),
                    ],
                    'problems' => collect(data_get($deviceNode, 'problems', []))->map(function ($problemNode) {
                        $problem = data_get($problemNode, 'problem', $problemNode);

                        return [
                            'problem' => [
                                'id' => data_get($problem, 'id'),
                                'label_ar' => data_get($problem, 'label_ar'),
                                'label_en' => data_get($problem, 'label_en'),
                                'sort_order' => data_get($problem, 'sort_order', 0),
                                'is_active' => (bool) data_get($problem, 'is_active', true),
                            ],
                            'solutions' => collect(data_get($problemNode, 'solutions', []))->map(function ($solutionNode) {
                                $solution = data_get($solutionNode, 'solution', $solutionNode);

                                return [
                                    'solution' => [
                                        'id' => data_get($solution, 'id'),
                                        'label_ar' => data_get($solution, 'label_ar'),
                                        'label_en' => data_get($solution, 'label_en'),
                                        'sort_order' => data_get($solution, 'sort_order', 0),
                                        'is_active' => (bool) data_get($solution, 'is_active', true),
                                    ],
                                    'fields' => collect(data_get($solutionNode, 'fields', data_get($solution, 'fields', [])))->map(function ($f) {
                                        return [
                                            'id' => data_get($f, 'id') ?? data_get($f, 'field_id'),
                                            'field_key' => data_get($f, 'field_key'),
                                            'label_ar' => data_get($f, 'label_ar'),
                                            'label_en' => data_get($f, 'label_en'),
                                            'field_type' => data_get($f, 'field_type'),
                                            'is_required' => (bool) data_get($f, 'is_required', false),
                                            'sort_order' => data_get($f, 'sort_order', 0),
                                        ];
                                    })->values(),
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
