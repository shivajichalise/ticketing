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
        $categories = [
            [
                'id' => 1,
                'parent_id' => null,
                'name' => 'First',
                'slug' => 'first',
                'path' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'name' => 'Second',
                'slug' => 'second',
                'path' => '1/2/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'parent_id' => 2,
                'name' => 'Third',
                'slug' => 'third',
                'path' => '1/2/3/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'parent_id' => 3,
                'name' => 'Fourth',
                'slug' => 'fourth',
                'path' => '1/2/3/4/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'parent_id' => 4,
                'name' => 'Fifth',
                'slug' => 'fifth',
                'path' => '1/2/3/4/5/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'parent_id' => 5,
                'name' => 'Sixth',
                'slug' => 'sixth',
                'path' => '1/2/3/4/5/6/',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Category::insert($categories);
    }
}
