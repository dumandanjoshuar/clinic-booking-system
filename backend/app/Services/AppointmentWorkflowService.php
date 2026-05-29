<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentStatusLog;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AppointmentWorkflowService
{
    public function __construct(
        private readonly AppointmentSlotService $slotService,
        private readonly AppointmentNotificationService $notificationService,
    ) {
    }

    public function changeStatus(Appointment $appointment, string $newStatus, ?int $userId, ?string $notes = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $newStatus, $userId, $notes): Appointment {
            $oldStatus = $appointment->status;

            $appointment->update([
                'status' => $newStatus,
                'cancellation_reason' => in_array($newStatus, [Appointment::STATUS_CANCELLED, Appointment::STATUS_REJECTED], true)
                    ? $notes
                    : $appointment->cancellation_reason,
            ]);

            $this->logStatus($appointment, $oldStatus, $newStatus, $userId, $notes);
            $fresh = $appointment->fresh(['patient', 'doctor', 'service']);
            $this->notificationService->statusChanged($fresh, $oldStatus);

            return $fresh;
        });
    }

    public function reschedule(
        Appointment $appointment,
        Doctor $doctor,
        string $date,
        string $startTime,
        ?int $userId,
        ?string $notes = null,
    ): Appointment {
        $service = $appointment->service;

        if (! $this->slotService->hasSlot($service, $doctor, $date, $startTime)) {
            abort(Response::HTTP_CONFLICT, 'The selected time slot is no longer available.');
        }

        $endTime = $this->slotService->endTime($startTime, $service);

        return DB::transaction(function () use ($appointment, $doctor, $date, $startTime, $endTime, $userId, $notes): Appointment {
            $hasOverlap = Appointment::query()
                ->where('id', '!=', $appointment->id)
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $date)
                ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->lockForUpdate()
                ->exists();

            if ($hasOverlap) {
                abort(Response::HTTP_CONFLICT, 'The selected time slot is no longer available.');
            }

            $oldStatus = $appointment->status;
            $appointment->update([
                'doctor_id' => $doctor->id,
                'appointment_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => Appointment::STATUS_CONFIRMED,
                'cancellation_reason' => null,
            ]);

            $this->logStatus($appointment, $oldStatus, Appointment::STATUS_CONFIRMED, $userId, $notes ?? 'Appointment rescheduled.');
            $fresh = $appointment->fresh(['patient', 'doctor', 'service']);
            $this->notificationService->rescheduled($fresh);

            return $fresh;
        });
    }

    private function logStatus(Appointment $appointment, ?string $oldStatus, string $newStatus, ?int $userId, ?string $notes): void
    {
        AppointmentStatusLog::query()->create([
            'appointment_id' => $appointment->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'notes' => $notes,
        ]);
    }
}
