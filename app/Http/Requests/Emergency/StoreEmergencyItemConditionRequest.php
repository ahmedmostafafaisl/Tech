<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyItemConditionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $sideRules = function ($side) {
            return [
                "$side.have_issue" => 'required|boolean',
                "$side.issues" => 'nullable|array',
                "$side.issues.*" => 'string',
                "$side.issue_note" => 'nullable|string',
                "$side.images" => 'nullable|array',
                "$side.images.*" => 'nullable|image|max:2048',
            ];
        };

        return array_merge(
            [
                'appointment_line_id' => 'required|exists:appointment_lines,id',
                'status' => 'required|in:return_for_refund,collect_for_maintenance,replace_with_new',
            ],
            $sideRules('right_side_conditon'),
            $sideRules('left_side_conditon'),
            $sideRules('front_side_conditon'),
            $sideRules('back_side_conditon'),
            $sideRules('top_side_conditon'),
        );
    }
}
