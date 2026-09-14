<?php

namespace App\Http\Controllers\Task;

use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Task\TaskResource;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Repositories\Interfaces\TaskRepositoryInterface;


class TaskController extends Controller
{
    use ApiResponseHelper;
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $priority = $request->input('priority');
        $status = $request->input('status');
        return   $this->taskRepository->getAllTasks($perPage, $page, $priority, $status);
    }

    public function show($id)
    {
        return $this->setCode(code: 200)->setData(new TaskResource($this->taskRepository->getTaskById($id)))->setMessage('success')->send();
    }

    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskRepository->createTask($request->validated());
        return $this->setCode(code: 200)->setData(new TaskResource($task))->setMessage('success')->send();
    }

    public function update(UpdateTaskRequest $request, $id)
    {
        $task = $this->taskRepository->updateTask($id, $request->validated());
        return $this->setCode(code: 200)->setData(new TaskResource($task))->setMessage('success')->send();
    }

    public function destroy($id)
    {
        $this->taskRepository->deleteTask($id);
        return $this->setCode(code: 200)->setData([])->setMessage('Task deleted successfully')->send();
    }

    public function getTechnicianTasks()
    {
        $user = auth()->user();

        if (!$user || $user->type !== 'tech') {
            return $this->setCode(403)
                ->setMessage('Unauthorized: Only technicians can access this endpoint.')
                ->send();
        }

        $tasks = $this->taskRepository->getTechnicianTasks($user->id);

        return $this->setCode(200)
            ->setData(TaskResource::collection($tasks))
            ->setMessage('success')
            ->send();
    }
}
