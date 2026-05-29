<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AppointmentSlotService
{
    public function availableSlots(Service $service, string $date, ?Doctor $doctor = null): array
    {
        $appointmentDate = Carbon::parse($date)->startOfDay();
        $dayOfWeek = $appointmentDate->dayOfWeek;

        $availabilities = DoctorAvailability::query()
            ->with('doctor')
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->whereHas('doctor', fn ($query) => $query->where('is_active', true))
            ->when($doctor, fn ($query) => $query->where('doctor_id', $doctor->id))
            ->orderBy('start_time')
            ->get();

        $doctorIds = $availabilities->pluck('doctor_id')->unique()->values();
        $bookedAppointments = Appointment::query()
            ->whereIn('doctor_id', $doctorIds)
            ->whereDate('appointment_date', $appointmentDate->toDateString())
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->get()
            ->groupBy('doctor_id');

        return $availabilities
            ->flatMap(fn (DoctorAvailability $availability) => $this->slotsForAvailability(
                $availability,
                $service,
                $appointmentDate,
                $bookedAppointments->get($availability->doctor_id, collect()),
            ))
            ->sortBy(['start_time', 'doctor.full_name'])
            ->values()
            ->all();
    }

    public function hasSlot(Service $service, Doctor $doctor, string $date, string $startTime): bool
    {
        return collect($this->availableSlots($service, $date, $doctor))
            ->contains(fn (array $slot) => $slot['start_time'] === $startTime);
    }

    public function endTime(string $startTime, Service $service): string
    {
        return Carbon::createFromFormat('H:i', $startTime)
            ->addMinutes($service->duration_minutes)
            ->format('H:i');
    }

    public function hasOverlap(Doctor $doctor, string $date, string $startTime, string $endTime, bool $lock = false): bool
    {
        $query = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    private function slotsForAvailability(
        DoctorAvailability $availability,
        Service $service,
        Carbon $date,
        Collection $bookedAppointments,
    ): Collection {
        $slots = collect();
        $slotStep = $availability->slot_duration_minutes;
        $duration = $service->duration_minutes;
        $cursor = Carbon::parse($date->toDateString().' '.$availability->start_time);
        $availabilityEnd = Carbon::parse($date->toDateString().' '.$availability->end_time);

        while ($cursor->copy()->addMinutes($duration)->lte($availabilityEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($duration);

            if (
                ! $this->overlapsBreak($availability, $date, $cursor, $slotEnd)
                && ! $this->overlapsBookedAppointment($bookedAppointments, $cursor, $slotEnd)
            ) {
                $slots->push([
                    'doctor_id' => $availability->doctor_id,
                    'doctor' => [
                        'id' => $availability->doctor->id,
                        'full_name' => $availability->doctor->full_name,
                        'specialization' => $availability->doctor->specialization,
                    ],
                    'date' => $date->toDateString(),
                    'start_time' => $cursor->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                ]);
            }

            $cursor->addMinutes($slotStep);
        }

        return $slots;
    }

    private function overlapsBreak(DoctorAvailability $availability, Carbon $date, Carbon $slotStart, Carbon $slotEnd): bool
    {
        if (! $availability->break_start || ! $availability->break_end) {
            return false;
        }

        $breakStart = Carbon::parse($date->toDateString().' '.$availability->break_start);
        $breakEnd = Carbon::parse($date->toDateString().' '.$availability->break_end);

        return $slotStart->lt($breakEnd) && $slotEnd->gt($breakStart);
    }

    private function overlapsBookedAppointment(Collection $appointments, Carbon $slotStart, Carbon $slotEnd): bool
    {
        return $appointments->contains(function (Appointment $appointment) use ($slotStart, $slotEnd): bool {
            $bookedStart = Carbon::parse($slotStart->toDateString().' '.$appointment->start_time);
            $bookedEnd = Carbon::parse($slotStart->toDateString().' '.$appointment->end_time);

            return $slotStart->lt($bookedEnd) && $slotEnd->gt($bookedStart);
        });
    }
}
