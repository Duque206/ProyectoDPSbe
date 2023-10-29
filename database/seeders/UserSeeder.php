<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $warehouses = Warehouse::select('id')->get();

        User::factory($warehouses->count())->sequence(fn ($sequence) => [ 'warehouse_id' => $warehouses[$sequence->index]->id ])->create();

        User::factory()->create([
            'name' => 'Director',
            'email' => 'director@example.com',
            'warehouse_id' => null,
            'role_id' => 1,
        ]);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'warehouse_id' => $warehouses[0]->id,
            'role_id' => 2,
        ]);

        User::factory()->create([
            'name' => 'Employee',
            'email' => 'employee@example.com',
            'warehouse_id' => $warehouses[0]->id,
            'role_id' => 3,
        ]);
    }
}
