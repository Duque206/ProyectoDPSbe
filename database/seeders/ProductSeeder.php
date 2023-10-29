<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = Category::select('id')->get();
        $warehouses = Warehouse::select('id')->get();

        Product::factory(500)->create()->each(function ($product) use ($categories, $warehouses) {
            foreach ($categories->random(rand(1, $categories->count())) as $category) {
                $product->categories()->attach($category);
            }

            foreach ($warehouses->random(rand(1, $warehouses->count())) as $warehouse) {
                $product->warehouses()->attach($warehouse, [
                    'quantity'=>random_int(0,50),
                ]);
            }
        });
    }
}
