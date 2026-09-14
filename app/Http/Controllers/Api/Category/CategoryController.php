<?php

namespace App\Http\Controllers\Api\Category;




use Illuminate\Http\Request;
use App\Services\DY365\DyService;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\CategoryRequest;
use App\Http\Resources\Category\CategoryResource;
use App\Repositories\Interfaces\CategoryRepositoryInterface;

class CategoryController extends Controller
{
    protected $categories;
    protected DyService $dynamicsService;

    public function __construct(DyService $dynamicsService, CategoryRepositoryInterface $categories)
    {
        $this->categories = $categories;

        $this->dynamicsService = $dynamicsService;
    }

    public function index(): JsonResponse
    {
        return response()->json(CategoryResource::collection($this->categories->all()));
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = $this->categories->create($request->validated());
        return response()->json(new CategoryResource($category), 201);
    }

    public function show($id): JsonResponse
    {
        $category = $this->categories->find($id);
        if (!$category) return response()->json(['message' => 'Not found'], 404);

        return response()->json(new CategoryResource($category));
    }

    public function update(CategoryRequest $request, $id): JsonResponse
    {
        $updated = $this->categories->update($id, $request->validated());
        return $updated
            ? response()->json(['message' => 'Updated successfully'])
            : response()->json(['message' => 'Not found'], 404);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->categories->delete($id);
        return $deleted
            ? response()->json(['message' => 'Deleted successfully'])
            : response()->json(['message' => 'Not found'], 404);
    }

    public function syncCategories(Request $request)
    {
        try {

            $response = $this->dynamicsService->getProductCategories();
            $this->categories->syncCategories($response['Data']['ProductCategories'] ?? []);
            return response()->json(['message' => 'Categories synced successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to sync categories: ' . $e->getMessage()], 500);
        }
    }
}
