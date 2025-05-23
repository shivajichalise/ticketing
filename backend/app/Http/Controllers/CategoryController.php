<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Facades\AuditLogger;
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
use Illuminate\Support\Benchmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class CategoryController extends Controller
{
    use RespondsWithJson;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $length = $request->integer('length', 10);
        $onlyParents = $request->boolean('only_parents', false);

        $categoryQuery = Category::query();

        if ($onlyParents) {
            $categoryQuery->parent();
        }

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

        AuditLogger::log($request->jwt_user_id, 'category_created', $category, [
            'name' => $category->name,
            'parent_id' => $category->parent_id,
        ]);

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
     * Display the breadcrumb trail for the given category.
     */
    public function breadcrumb(Category $category): JsonResponse
    {
        DB::enableQueryLog();

        [$breadcrumb, $durationMs] = Benchmark::value(
            fn (): string => $category->ancestors->pluck('name')->implode(' > ') . ' > ' . $category->name
        );

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalDbTimeMs = collect($queries)->sum('time');

        $durationSeconds = round($durationMs / 1000, 6);
        $totalDbTimeSeconds = round($totalDbTimeMs / 1000, 6);

        Log::channel('benchmark')->info('Category breadcrumb benchmark', [
            'category_id' => $category->id,
            'duration_seconds' => $durationSeconds,
            'query_count' => $queryCount,
            'db_time_seconds' => $totalDbTimeSeconds,
        ]);

        return $this->success([
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Display the descendants for the given category.
     */
    public function descendants(Category $category): AnonymousResourceCollection
    {
        DB::enableQueryLog();

        [$descendants, $durationMs] = Benchmark::value(
            fn (): Collection => $category->descendants
        );

        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        $totalDbTimeMs = collect($queries)->sum('time');

        $durationSeconds = round($durationMs / 1000, 6);
        $totalDbTimeSeconds = round($totalDbTimeMs / 1000, 6);

        Log::channel('benchmark')->info('Category descendants benchmark', [
            'category_id' => $category->id,
            'duration_seconds' => $durationSeconds,
            'query_count' => $queryCount,
            'db_time_seconds' => $totalDbTimeSeconds,
        ]);

        return CategoryResource::collection($descendants)->additional([
            'status' => true,
            'message' => 'Descendant categories fetched successfully.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $fields = $request->validated();

        $parentIdChanged = $fields['parent_id'] !== $category->parent_id;

        $original = $category->only(['name', 'parent_id']);

        $category->update($fields);

        if ($parentIdChanged) {
            RebuildCategoriesPathJob::dispatch($category->id, $fields['parent_id'] ?? null);
        }

        AuditLogger::log($request->jwt_user_id, 'category_updated', $category, [
            'old' => $original,
            'new' => $category->only(['name', 'parent_id']),
        ]);

        return (new CategoryResource($category))->additional([
            'status' => true,
            'message' => 'Category updated succesfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): JsonResponse
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

        AuditLogger::log($request->jwt_user_id, 'category_deleted', $category, [
            'id' => $category->id,
            'name' => $category->name,
            'path' => $category->path,
        ]);

        return $this->success([], 'Category soft deleted.', 204);
    }
}
