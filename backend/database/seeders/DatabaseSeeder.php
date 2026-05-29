<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        if (filter_var(env('CLINIC_SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(ClinicDemoSeeder::class);
        }
    }
}
