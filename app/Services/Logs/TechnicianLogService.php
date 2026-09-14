<?php

namespace App\Services\Logs;

use App\Models\TechnicianLog;

class TechnicianLogService
{

    public function log($techId, $actionType, $description, $actionBy = null, $data = [])
    {
        TechnicianLog::create([
            'tech_id'     => $techId,
            'action_type' => $actionType,
            'description' => $description,
            'action_by'   => $actionBy,
            'data'        => $data,
        ]);
    }
}
