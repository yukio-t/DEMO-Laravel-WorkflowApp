<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'applicant@example.com'],
            ['name' => 'Applicant', 'password' => Hash::make('password'), 'role' => 'applicant']
        );
        User::firstOrCreate(
            ['email' => 'approver@example.com'],
            ['name' => 'Approver', 'password' => Hash::make('password'), 'role' => 'approver']
        );
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin']
        );
    }
}
