<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\AppointmentStatusLog;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Service;
use App\Services\AppointmentNotificationService;
use App\Services\AppointmentSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function store(
        StoreAppointmentRequest $request,
        AppointmentSlotService $slotService,
        AppointmentNotificationService $notificationService,
    ): JsonResponse {
        $data = $request->validated();
        $service = Service::query()->where('is_active', true)->findOrFail($data['service_id']);
        $doctor = Doctor::query()->where('is_active', true)->findOrFail($data['doctor_id']);

        if (! $slotService->hasSlot($service, $doctor, $data['appointment_date'], $data['start_time'])) {
            return response()->json([
                'message' => 'The selected time slot is no longer available.',
            ], Response::HTTP_CONFLICT);
        }

        $endTime = $slotService->endTime($data['start_time'], $service);

        $appointment = DB::transaction(function () use ($data, $doctor, $service, $endTime, $slotService): Appointment {
            if ($slotService->hasOverlap($doctor, $data['appointment_date'], $data['start_time'], $endTime, lock: true)) {
                abort(Response::HTTP_CONFLICT, 'The selected time slot is no longer available.');
            }

            $patient = Patient::query()->updateOrCreate(
                ['email' => $data['patient']['email']],
                [
                    'full_name' => $data['patient']['full_name'],
                    'phone' => $data['patient']['phone'],
                ],
            );

            $appointment = Appointment::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'service_id' => $service->id,
                'appointment_date' => $data['appointment_date'],
                'start_time' => $data['start_time'],
                'end_time' => $endTime,
                'status' => Appointment::STATUS_PENDING,
                'reason' => $data['reason'] ?? null,
            ]);

            AppointmentStatusLog::query()->create([
                'appointment_id' => $appointment->id,
                'old_status' => null,
                'new_status' => Appointment::STATUS_PENDING,
                'changed_by' => null,
                'notes' => 'Appointment request submitted by patient.',
            ]);

            return $appointment;
        });

        $notificationService->bookingSubmitted($appointment->load(['patient', 'doctor', 'service']));

        return response()->json([
            'message' => 'Appointment request submitted successfully.',
            'data' => AppointmentResource::make($appointment->load(['patient', 'doctor', 'service'])),
        ], Response::HTTP_CREATED);
    }
}
