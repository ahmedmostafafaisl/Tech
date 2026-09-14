<?php

namespace App\Repositories\Task;

use App\Models\Task;
use App\Models\TaskImage;
use App\Helper\ApiResponseHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Task\TaskResource;
use App\Repositories\Interfaces\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{

    use ApiResponseHelper;
    public function getAllTasks($perPage, $page, $priority = null, $status = null)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view tasks')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this tasks.')
                ->send();
        }

        $query = Task::query()
            ->with('images')

            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($priority, function ($query, $priority) {
                return $query->where('priority', $priority);
            });
        // Paginate the results
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return $this->setCode(200)
            ->setData([
                'tasks' => TaskResource::collection($users->items()),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total_items' => $users->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }
    public function getTaskById($id)
    {
        return Task::with('images')->findOrFail($id);
    }

    public function createTask(array $data)
    {
        $task = Task::create($data);
        // $images = $this->storeImages($data['data'], "Appointments/After/{$id}");
        if (isset($data['images'])) {
            $this->handleImages($task, $data['images'], "Tasks/{$task->id}");
        }


        return $task;
    }

    public function updateTask($id, array $data)
    {
        $task = Task::findOrFail($id);
        $task->update($data);

        if (isset($data['images'])) {
            // Delete old images
            foreach ($task->images as $image) {
                Storage::delete('public/Tasks/' . $image->image);
                $image->delete();
            }

            // Upload new images
            $this->handleImages($task, $data['images'], "Tasks/{$task->id}");

            // Reload the images relationship
            $task->load('images');
        }

        return $task;
    }


    public function deleteTask($id)
    {
        $task = Task::findOrFail($id);

        // Delete associated images
        foreach ($task->images as $image) {
            Storage::delete('public/' . $image->image);
            $image->delete();
        }

        return $task->delete();
    }

    private function handleImages(Task $task, $images, string $path)
    {

        foreach ($images as $image) {
            if ($image && $image->isValid()) {
                $extension = $image->getClientOriginalExtension();
                $fileName = uniqid('task_', true) . '.' . $extension;
                $imagePath = $image->storeAs('Tasks/' . $task->id, $fileName, 's3');
                // dd($imagePath);
                TaskImage::create([
                    'task_id' => $task->id,
                    'image' => $imagePath,
                ]);
            }
        }
    }



    public function getTechnicianTasks(int $technicianId)
    {
        return Task::with('images')->where('technician_id', $technicianId)->get();
    }
}
