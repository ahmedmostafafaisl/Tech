<?php

namespace App\Http\Resources\Emergency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyItemConditionResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_line_id' => $this->appointment_line_id,
            'status' => $this->status,

            'right_side' => [
                'have_issue' => $this->right_side_have_issue,
                'issues' => $this->right_side_issues,
                'issue_note' => $this->right_side_issue_note,
            ],
            'left_side' => [
                'have_issue' => $this->left_side_have_issue,
                'issues' => $this->left_side_issues,
                'issue_note' => $this->left_side_issue_note,
            ],
            'front_side' => [
                'have_issue' => $this->front_side_have_issue,
                'issues' => $this->front_side_issues,
                'issue_note' => $this->front_side_issue_note,
            ],
            'back_side' => [
                'have_issue' => $this->back_side_have_issue,
                'issues' => $this->back_side_issues,
                'issue_note' => $this->back_side_issue_note,
            ],
            'top_side' => [
                'have_issue' => $this->top_side_have_issue,
                'issues' => $this->top_side_issues,
                'issue_note' => $this->top_side_issue_note,
            ],

            'images' => $this->images->map(function ($image) {
                return [
                    'side' => $image->side,
                    // 'path' => asset('storage/' . $image->path),
                    'path' => $image->path
                        ? Storage::disk('s3')->temporaryUrl($image->path, now()->addMinutes(100))
                        : null,
                ];
            }),
        ];
    }
}
