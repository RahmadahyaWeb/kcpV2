<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SUPER
        // Role::create(['name' => 'super-user']);
        // Role::create(['name' => 'admin']);

        // MARKETING
        // Role::create(['name' => 'supervisor-area']);
        // Role::create(['name' => 'head-marketing']);
        // Role::create(['name' => 'salesman']);
        // Role::create(['name' => 'fakturis']);

        // WAREHOUSE
        // Role::create(['name' => 'head-warehouse']);
        // Role::create(['name' => 'storer']);
        // Role::create(['name' => 'inventory']);
        Role::create(['name' => 'driver']);

        // FINANCE
        // Role::create(['name' => 'finance']);
        // Role::create(['name' => 'ar']);
    }
}
