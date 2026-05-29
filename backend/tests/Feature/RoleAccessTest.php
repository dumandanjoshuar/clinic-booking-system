<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_doctor_routes_are_role_protected(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $doctorUser = User::query()->create([
            'name' => 'Doctor User',
            'email' => 'doctor@example.com',
            'password' => 'password',
            'role' => User::ROLE_DOCTOR,
        ]);

        Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'full_name' => 'Doctor User',
            'email' => 'doctor@example.com',
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/dashboard')->assertOk();
        $this->getJson('/api/doctor/dashboard')->assertForbidden();

        Sanctum::actingAs($doctorUser);
        $this->getJson('/api/doctor/dashboard')->assertOk();
        $this->getJson('/api/admin/dashboard')->assertForbidden();
    }
}
