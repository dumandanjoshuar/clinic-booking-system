<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('CLINIC_ADMIN_EMAIL', ''));
        $password = (string) env('CLINIC_ADMIN_PASSWORD', '');
        $name = trim((string) env('CLINIC_ADMIN_NAME', 'Clinic Admin'));
        $resetPassword = filter_var(env('CLINIC_ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || $password === '') {
            $this->command?->warn('Skipping admin seed. Set CLINIC_ADMIN_EMAIL and CLINIC_ADMIN_PASSWORD to create the first admin.');

            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->name = $name !== '' ? $name : 'Clinic Admin';
        $user->role = User::ROLE_ADMIN;

        if (! $user->exists || $resetPassword) {
            $user->password = Hash::make($password);
            $user->must_change_password = false;
        }

        $user->save();

        $this->command?->info("Admin account is ready: {$email}");
    }
}
