<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Jobs\RebuildCategoriesPathJob;
use App\Models\Category;
use App\Traits\RespondsWithJson;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    use RespondsWithJson;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $length = $request->integer('length', 10);

        $categoryQuery = Category::query();

        $categories = $categoryQuery->simplePaginate($length);

        return CategoryResource::collection($categories)->additional([
            'status' => true,
            'message' => 'Categories fetched succesfully.',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $fields = $request->validated();

        $category = Category::create($fields);

        $materializedPath = '';

        $parentId = $category->parent_id;

        if ($parentId) {
            $parent = Category::select('id', 'path')->find($parentId);

            if ($parent->path) {
                $materializedPath .= "{$parent->path}{$category->id}/";
            } else {
                $materializedPath .= "{$parent->id}/{$category->id}/";
            }
        }

        $category->path = $materializedPath;
        $category->save();

        return (new CategoryResource($category))->additional([
            'message' => 'Category created successfully',
            'status' => true,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): CategoryResource
    {
        // $category->load(['parent', 'childrens']);

        return (new CategoryResource($category))->additional([
            'status' => true,
            'message' => 'Category fetched succesfully.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $fields = $request->validated();

        $parentIdChanged = $fields['parent_id'] !== $category->parent_id;

        $category->update($fields);

        if ($parentIdChanged) {
            RebuildCategoriesPathJob::dispatch($category->id, $fields['parent_id'] ?? null);
        }

        return (new CategoryResource($category))->additional([
            'status' => true,
            'message' => 'Category updated succesfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $hasChildrens = Category::where('parent_id', $category->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasChildrens) {
            return $this->error(
                new Exception('This category has active child categories and cannot be deleted.'),
                'Please delete or reassign its child categories before attempting to delete this one.',
                409
            );
        }

        $category->delete();

        return $this->success([], 'Category soft deleted.', 204);
    }
}
