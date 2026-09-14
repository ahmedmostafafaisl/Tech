<?php

namespace App\Repositories\User;


use App\Models\UserLog;
use App\Repositories\Interfaces\UserLogRepositoryInterface;

class UserLogRepository implements UserLogRepositoryInterface
{
    public function all()
    {
        return UserLog::latest()->get();
    }

    public function find($id)
    {
        return UserLog::findOrFail($id);
    }

    public function create(array $data)
    {
        return UserLog::create($data);
    }

    public function update($id, array $data)
    {
        $log = UserLog::findOrFail($id);
        $log->update($data);
        return $log;
    }

    public function delete($id)
    {
        $log = UserLog::findOrFail($id);
        return $log->delete();
    }

    public function getAuthUserLogs($userId)
    {
        return UserLog::where('user_id', $userId)->latest()->get();
    }
}
