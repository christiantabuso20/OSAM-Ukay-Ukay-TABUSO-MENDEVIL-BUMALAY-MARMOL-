<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $this->call(ProductSeeder::class);

        // Change these passwords after your first login.
        User::updateOrCreate(
            ['email' => 'admin@osam.test'],
            ['name' => 'OSAM Admin', 'username' => 'admin', 'password' => 'admin123', 'role' => User::ROLE_ADMIN, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'staff@osam.test'],
            ['name' => 'OSAM Staff', 'username' => 'staff', 'password' => 'staff123', 'role' => User::ROLE_STAFF, 'is_active' => true]
        );
    }
}
