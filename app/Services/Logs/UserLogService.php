<?php

namespace App\Services\Logs;

use App\Models\UserLog;
use Illuminate\Support\Facades\Auth;

class UserLogService
{
    public function create(
        string $action,
        ?string $descriptionEn = null,
        ?string $descriptionAr = null,
        array $body = [],
        ?int $userId = null,
        array $response = []
    ): UserLog {
        return UserLog::create([
            'user_id'        => $userId ?? Auth::id(),
            'action'         => $action,
            'description_en' => $descriptionEn ?? '',
            'description_ar' => $descriptionAr ?? '',
            'body'           => !empty($body) ? $body : null,
            'response'       => !empty($response) ? $response : null,
            'action_date'    => now(),
        ]);
    }
}
