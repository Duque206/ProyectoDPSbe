<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'director',
            'administrator',
            'employee',
        ];

        Role::factory(count($roles))->sequence(fn ($sqn) => ['type' => $roles[$sqn->index]])->create();
    }
}
