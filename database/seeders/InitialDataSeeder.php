<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create an initial admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@racik.my.id',
            'phone' => '081234567890',
            'password' => bcrypt(env('ADMIN_PASSWORD', 'password')),
            'role' => 'admin'
        ]);
    }
}
