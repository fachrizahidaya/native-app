<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VerifiedUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_verified' => true,
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]
        );

        // Create Member User
        User::updateOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member123'),
                'role' => 'member',
                'is_verified' => true,
                'email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null,
            ]
        );

        $this->command->info('✓ Admin User created - Email: admin@example.com, Password: admin123');
        $this->command->info('✓ Member User created - Email: member@example.com, Password: member123');
        $this->command->info('');
        $this->command->info('Both users are verified and ready to use!');
    }
}
