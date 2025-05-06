<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class RebuildCategoriesPathJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $categoryId,
        public ?int $newParentId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $category = Category::find($this->categoryId);

        $newParentPath = '';
        if ($this->newParentId) {
            $parent = Category::select('id', 'path')->find($this->newParentId);
            $newParentPath = $parent->path ?? ($parent->id . '/');
        }

        if ($newParentPath !== '' && ! str_ends_with($newParentPath, '/')) {
            $newParentPath .= '/';
        }

        $oldPath = $category->path;
        $newPath = $newParentPath . $category->id . '/';

        DB::transaction(function () use ($category, $newPath, $oldPath): void {
            $category->update([
                'path' => $newPath,
            ]);

            $descendants = Category::where('path', 'like', "{$oldPath}%")->get();

            foreach ($descendants as $descendant) {
                $descendant->path = $newPath . mb_substr($descendant->path, mb_strlen($oldPath));
                $descendant->save();
            }
        });
    }
}
