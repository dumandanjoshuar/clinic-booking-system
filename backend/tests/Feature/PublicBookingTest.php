<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_creates_pending_appointment_and_blocks_slot(): void
    {
        Carbon::setTestNow('2026-05-29 09:00:00');

        $doctor = Doctor::query()->create([
            'full_name' => 'Dr. Test Doctor',
            'email' => 'doctor@example.com',
            'is_active' => true,
        ]);

        $service = Service::query()->create([
            'name' => 'General Consultation',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        DoctorAvailability::query()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $payload = [
            'service_id' => $service->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => '2026-06-01',
            'start_time' => '09:00',
            'patient' => [
                'full_name' => 'Test Patient',
                'email' => 'patient@example.com',
                'phone' => '09170000000',
            ],
            'reason' => 'Fever and cough.',
        ];

        $this->postJson('/api/public/appointments', $payload)
            ->assertCreated()
            ->assertJsonPath('data.status', Appointment::STATUS_PENDING);

        $this->assertDatabaseHas('appointments', [
            'doctor_id' => $doctor->id,
            'service_id' => $service->id,
            'appointment_date' => '2026-06-01',
            'start_time' => '09:00',
            'end_time' => '09:30',
            'status' => Appointment::STATUS_PENDING,
        ]);

        $this->getJson("/api/public/available-slots?service_id={$service->id}&doctor_id={$doctor->id}&date=2026-06-01")
            ->assertOk()
            ->assertJsonMissing(['start_time' => '09:00'])
            ->assertJsonFragment(['start_time' => '09:30']);
    }
}
