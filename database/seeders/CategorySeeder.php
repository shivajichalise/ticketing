<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $categories = [];

        for ($i = 1; $i <= 88; $i++) {
            $categories[] = [
                'id' => $i,
                'parent_id' => $i === 1 ? null : $i - 1,
                'name' => "Level {$i}",
                'slug' => 'level-' . $i,
                'path' => $i === 1
                    ? null
                    : implode('/', range(1, $i)) . '/',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Category::insert($categories);
    }
}
