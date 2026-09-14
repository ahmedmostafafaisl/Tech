<?php

namespace App\Http\Controllers\Api\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserLogRequest;
use App\Http\Resources\User\UserLogResource;
use App\Repositories\Interfaces\UserLogRepositoryInterface;

class UserLogController extends Controller
{
    protected $userLogRepo;

    public function __construct(UserLogRepositoryInterface $userLogRepo)
    {
        $this->userLogRepo = $userLogRepo;
    }

    public function index()
    {
        return UserLogResource::collection($this->userLogRepo->all());
    }

    public function store(UserLogRequest $request)
    {
        $log = $this->userLogRepo->create($request->validated());
        return new UserLogResource($log);
    }

    public function show($id)
    {
        return new UserLogResource($this->userLogRepo->find($id));
    }

    public function update(UserLogRequest $request, $id)
    {
        $log = $this->userLogRepo->update($id, $request->validated());
        return new UserLogResource($log);
    }

    public function destroy($id)
    {
        $this->userLogRepo->delete($id);
        return response()->json(['message' => 'User log deleted successfully']);
    }
    public function getAuthUserLogs(Request $request)
    {
        $userId = $request->user()->id;
        $logs = $this->userLogRepo->getAuthUserLogs($userId);
        return UserLogResource::collection($logs);
    }
}
