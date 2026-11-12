<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['T-Shirts', 'Polo Shirts', 'Pants', 'Shorts', 'Dresses', 'Jackets', 'Sweaters', 'Skirts', 'Blouses', 'Others'] as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
