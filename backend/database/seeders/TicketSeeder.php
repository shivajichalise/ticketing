<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

final class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::query()->select('id')->lazy();

        if ($categoryIds->isEmpty()) {
            $this->command->warn('No categories found. Please run CategorySeeder first.');

            return;
        }

        $categoryArray = $categoryIds->all();

        $ids = array_map(fn ($cat) => $cat->id, $categoryArray);

        foreach (range(1, 1000) as $i) {
            Ticket::create([
                'name' => "Ticket #{$i}",
                'category_id' => $ids[array_rand($ids)],
                'price' => rand(100, 500) / 10,
            ]);
        }
    }
}
