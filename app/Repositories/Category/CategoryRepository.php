<?php

namespace App\Repositories\Category;


use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\CategoryRepositoryInterface;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function all(): Collection
    {
        return Category::all();
    }

    public function find(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $category = $this->find($id);
        return $category ? $category->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $category = $this->find($id);
        return $category ? $category->delete() : false;
    }
    public function syncCategories(array $categories): void
    {
        DB::beginTransaction();
        try {
            foreach ($categories as $categoryData) {
                Category::updateOrCreate(
                    ['product_category_id' => $categoryData['ProductCategoryId']],
                    [
                        'name' => $categoryData['Name'],
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to sync categories: " . $e->getMessage());
        }
    }
}
