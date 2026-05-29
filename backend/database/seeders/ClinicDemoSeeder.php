<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentStatusLog;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ClinicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Clinic Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        $doctorUsers = collect([
            [
                'name' => 'Dr. Maria Santos',
                'email' => 'maria.santos@example.com',
                'phone' => '0917 410 2231',
                'specialization' => 'Family Medicine',
            ],
            [
                'name' => 'Dr. Miguel Reyes',
                'email' => 'miguel.reyes@example.com',
                'phone' => '0918 522 1940',
                'specialization' => 'Internal Medicine',
            ],
            [
                'name' => 'Dr. Andrea Lim',
                'email' => 'andrea.lim@example.com',
                'phone' => '0919 304 7788',
                'specialization' => 'Pediatrics',
            ],
            [
                'name' => 'Dr. Paolo Cruz',
                'email' => 'paolo.cruz@example.com',
                'phone' => '0920 885 6124',
                'specialization' => 'Dermatology',
            ],
        ])->map(function (array $doctor) {
            $user = User::query()->updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'name' => $doctor['name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_DOCTOR,
                ],
            );

            return Doctor::query()->updateOrCreate(
                ['email' => $doctor['email']],
                [
                    'user_id' => $user->id,
                    'full_name' => $doctor['name'],
                    'phone' => $doctor['phone'],
                    'specialization' => $doctor['specialization'],
                    'is_active' => true,
                ],
            );
        });

        $services = collect([
            [
                'name' => 'General Consultation',
                'description' => 'Routine checkup, common symptoms, and general health concerns.',
                'duration_minutes' => 30,
                'price' => 650,
            ],
            [
                'name' => 'Follow-up Consultation',
                'description' => 'Review of progress, medication response, or previous treatment plan.',
                'duration_minutes' => 20,
                'price' => 450,
            ],
            [
                'name' => 'Pediatric Consultation',
                'description' => 'Consultation for infants, children, and adolescents.',
                'duration_minutes' => 30,
                'price' => 700,
            ],
            [
                'name' => 'Annual Physical Exam',
                'description' => 'Preventive wellness visit with basic assessment and recommendations.',
                'duration_minutes' => 45,
                'price' => 1200,
            ],
            [
                'name' => 'Skin Consultation',
                'description' => 'Assessment for acne, rashes, allergies, and other skin concerns.',
                'duration_minutes' => 30,
                'price' => 850,
            ],
            [
                'name' => 'Blood Pressure Check',
                'description' => 'Quick visit for blood pressure monitoring and lifestyle guidance.',
                'duration_minutes' => 15,
                'price' => 250,
            ],
        ])->map(fn (array $service) => Service::query()->updateOrCreate(
            ['name' => $service['name']],
            $service + ['is_active' => true],
        ));

        $availabilityRules = [
            ['doctor' => 'maria.santos@example.com', 'days' => [1, 3, 5], 'start' => '09:00', 'end' => '16:00', 'break_start' => '12:00', 'break_end' => '13:00', 'slot' => 30],
            ['doctor' => 'miguel.reyes@example.com', 'days' => [2, 4], 'start' => '10:00', 'end' => '17:00', 'break_start' => '13:00', 'break_end' => '14:00', 'slot' => 30],
            ['doctor' => 'andrea.lim@example.com', 'days' => [1, 2, 4], 'start' => '08:30', 'end' => '15:30', 'break_start' => '12:00', 'break_end' => '13:00', 'slot' => 30],
            ['doctor' => 'paolo.cruz@example.com', 'days' => [3, 5, 6], 'start' => '11:00', 'end' => '18:00', 'break_start' => '14:00', 'break_end' => '15:00', 'slot' => 30],
        ];

        foreach ($availabilityRules as $rule) {
            $doctor = $doctorUsers->firstWhere('email', $rule['doctor']);

            foreach ($rule['days'] as $day) {
                DoctorAvailability::query()->updateOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'day_of_week' => $day,
                        'start_time' => $rule['start'],
                    ],
                    [
                        'end_time' => $rule['end'],
                        'break_start' => $rule['break_start'],
                        'break_end' => $rule['break_end'],
                        'slot_duration_minutes' => $rule['slot'],
                        'is_active' => true,
                    ],
                );
            }
        }

        $patients = collect([
            ['full_name' => 'Elaine Garcia', 'email' => 'elaine.garcia@example.com', 'phone' => '0917 840 1122', 'birthdate' => '1992-04-18', 'gender' => 'Female'],
            ['full_name' => 'Carlos Mendoza', 'email' => 'carlos.mendoza@example.com', 'phone' => '0918 331 9090', 'birthdate' => '1985-09-07', 'gender' => 'Male'],
            ['full_name' => 'Rina Villanueva', 'email' => 'rina.villanueva@example.com', 'phone' => '0919 772 6401', 'birthdate' => '1998-11-22', 'gender' => 'Female'],
            ['full_name' => 'Noel Bautista', 'email' => 'noel.bautista@example.com', 'phone' => '0920 654 1188', 'birthdate' => '1976-02-12', 'gender' => 'Male'],
            ['full_name' => 'Sofia Navarro', 'email' => 'sofia.navarro@example.com', 'phone' => '0916 210 4470', 'birthdate' => '2018-06-03', 'gender' => 'Female'],
            ['full_name' => 'Mark Dizon', 'email' => 'mark.dizon@example.com', 'phone' => '0915 408 9931', 'birthdate' => '1990-12-15', 'gender' => 'Male'],
        ])->map(fn (array $patient) => Patient::query()->updateOrCreate(
            ['email' => $patient['email']],
            $patient,
        ));

        $this->seedAppointments($patients, $doctorUsers, $services, $admin);
    }

    private function seedAppointments($patients, $doctors, $services, User $admin): void
    {
        $appointments = [
            [
                'patient' => 'elaine.garcia@example.com',
                'doctor' => 'maria.santos@example.com',
                'service' => 'General Consultation',
                'date' => Carbon::today()->addDays(1),
                'start' => '09:00',
                'end' => '09:30',
                'status' => Appointment::STATUS_PENDING,
                'reason' => 'Mild fever and sore throat for two days.',
                'admin_notes' => 'New online request. Needs triage confirmation.',
            ],
            [
                'patient' => 'carlos.mendoza@example.com',
                'doctor' => 'miguel.reyes@example.com',
                'service' => 'Annual Physical Exam',
                'date' => Carbon::today()->addDays(2),
                'start' => '10:00',
                'end' => '10:45',
                'status' => Appointment::STATUS_CONFIRMED,
                'reason' => 'Annual company medical clearance.',
                'admin_notes' => 'Patient confirmed by email.',
            ],
            [
                'patient' => 'sofia.navarro@example.com',
                'doctor' => 'andrea.lim@example.com',
                'service' => 'Pediatric Consultation',
                'date' => Carbon::today()->addDays(3),
                'start' => '08:30',
                'end' => '09:00',
                'status' => Appointment::STATUS_CONFIRMED,
                'reason' => 'Persistent cough and runny nose.',
                'admin_notes' => 'Guardian will accompany patient.',
            ],
            [
                'patient' => 'rina.villanueva@example.com',
                'doctor' => 'paolo.cruz@example.com',
                'service' => 'Skin Consultation',
                'date' => Carbon::today()->subDays(2),
                'start' => '11:00',
                'end' => '11:30',
                'status' => Appointment::STATUS_COMPLETED,
                'reason' => 'Acne flare-up and skin irritation.',
                'admin_notes' => 'Completed visit.',
            ],
            [
                'patient' => 'noel.bautista@example.com',
                'doctor' => 'miguel.reyes@example.com',
                'service' => 'Blood Pressure Check',
                'date' => Carbon::today()->subDay(),
                'start' => '14:00',
                'end' => '14:15',
                'status' => Appointment::STATUS_NO_SHOW,
                'reason' => 'Routine blood pressure monitoring.',
                'admin_notes' => 'Patient did not arrive after 20-minute grace period.',
            ],
            [
                'patient' => 'mark.dizon@example.com',
                'doctor' => 'maria.santos@example.com',
                'service' => 'Follow-up Consultation',
                'date' => Carbon::today()->addDays(4),
                'start' => '13:00',
                'end' => '13:20',
                'status' => Appointment::STATUS_CANCELLED,
                'reason' => 'Follow-up after medication adjustment.',
                'admin_notes' => 'Cancelled by patient.',
                'cancellation_reason' => 'Patient requested to move appointment to next week.',
            ],
        ];

        foreach ($appointments as $item) {
            $patient = $patients->firstWhere('email', $item['patient']);
            $doctor = $doctors->firstWhere('email', $item['doctor']);
            $service = $services->firstWhere('name', $item['service']);

            $appointment = Appointment::query()->updateOrCreate(
                [
                    'doctor_id' => $doctor->id,
                    'appointment_date' => $item['date']->toDateString(),
                    'start_time' => $item['start'],
                ],
                [
                    'patient_id' => $patient->id,
                    'service_id' => $service->id,
                    'end_time' => $item['end'],
                    'status' => $item['status'],
                    'reason' => $item['reason'],
                    'admin_notes' => $item['admin_notes'],
                    'cancellation_reason' => $item['cancellation_reason'] ?? null,
                ],
            );

            AppointmentStatusLog::query()->updateOrCreate(
                [
                    'appointment_id' => $appointment->id,
                    'new_status' => Appointment::STATUS_PENDING,
                ],
                [
                    'old_status' => null,
                    'changed_by' => null,
                    'notes' => 'Appointment request created from demo seed data.',
                ],
            );

            if ($item['status'] !== Appointment::STATUS_PENDING) {
                AppointmentStatusLog::query()->updateOrCreate(
                    [
                        'appointment_id' => $appointment->id,
                        'new_status' => $item['status'],
                    ],
                    [
                        'old_status' => Appointment::STATUS_PENDING,
                        'changed_by' => $admin->id,
                        'notes' => $item['admin_notes'],
                    ],
                );
            }
        }
    }
}
