<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $technicians = User::where('type', 'tech')->get();

        foreach ($technicians as $technician) {
            // Create random number of tasks (e.g., 3 to 7)
            $count = rand(3, 7);

            for ($i = 0; $i < $count; $i++) {
                Task::create([
                    'technician_id' => $technician->id,
                    'type' => ['maintenance', 'installation', 'inspection'][array_rand(['maintenance', 'installation', 'inspection'])],
                    'name' => 'Task ' . Str::random(5),
                    'description' => 'Description for task ' . Str::random(10),
                    'status' => ['pending', 'completed', 'canceled'][rand(0, 2)],
                    'priority' => ['low', 'medium', 'high'][rand(0, 2)],
                    'due_date' => now()->addDays(rand(1, 10)),
                    'note' => 'Note for task ' . Str::random(10),
                ]);
            }
        }
    }
}
