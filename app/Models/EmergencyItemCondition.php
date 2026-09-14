<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyItemCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_main_item_id',
        'status',

        'right_side_have_issue',
        'right_side_issues',
        'right_side_issue_note',

        'left_side_have_issue',
        'left_side_issues',
        'left_side_issue_note',

        'front_side_have_issue',
        'front_side_issues',
        'front_side_issue_note',

        'back_side_have_issue',
        'back_side_issues',
        'back_side_issue_note',

        'top_side_have_issue',
        'top_side_issues',
        'top_side_issue_note',
    ];

    protected $casts = [
        'right_side_issues' => 'array',
        'left_side_issues' => 'array',
        'front_side_issues' => 'array',
        'back_side_issues' => 'array',
        'top_side_issues' => 'array',
    ];


    public function mainItem()
    {
        return $this->belongsTo(EmergencyMainItem::class, 'emergency_main_item_id');
    }
    public function images()
    {
        return $this->hasMany(EmergencyItemConditionImage::class, 'condition_id');
    }
}
