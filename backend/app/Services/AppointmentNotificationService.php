<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppointmentNotificationService
{
    public function bookingSubmitted(Appointment $appointment): void
    {
        $appointment->loadMissing(['patient', 'doctor', 'service']);

        $this->sendPatientMail(
            $appointment,
            'Appointment request received',
            "Hi {$appointment->patient->full_name},\n\nWe received your appointment request for {$appointment->service->name} with {$appointment->doctor->full_name} on {$appointment->appointment_date->toDateString()} at {$this->time($appointment->start_time)}.\n\nYour request is pending clinic approval. We will contact you once it is reviewed.",
        );

        $adminEmail = config('mail.from.address');
        if ($adminEmail) {
            $this->sendRawMail(
                $adminEmail,
                'New appointment request',
                "A new appointment request was submitted by {$appointment->patient->full_name} for {$appointment->service->name} on {$appointment->appointment_date->toDateString()} at {$this->time($appointment->start_time)}.",
            );
        }
    }

    public function statusChanged(Appointment $appointment, string $oldStatus): void
    {
        $appointment->loadMissing(['patient', 'doctor', 'service']);

        $subject = match ($appointment->status) {
            Appointment::STATUS_CONFIRMED => 'Appointment confirmed',
            Appointment::STATUS_REJECTED => 'Appointment request rejected',
            Appointment::STATUS_CANCELLED => 'Appointment cancelled',
            default => null,
        };

        if (! $subject) {
            return;
        }

        $body = match ($appointment->status) {
            Appointment::STATUS_CONFIRMED => "Hi {$appointment->patient->full_name},\n\nYour appointment for {$appointment->service->name} with {$appointment->doctor->full_name} has been confirmed for {$appointment->appointment_date->toDateString()} at {$this->time($appointment->start_time)}.",
            Appointment::STATUS_REJECTED => "Hi {$appointment->patient->full_name},\n\nYour appointment request for {$appointment->service->name} on {$appointment->appointment_date->toDateString()} was not approved.\n\nReason: {$appointment->cancellation_reason}",
            Appointment::STATUS_CANCELLED => "Hi {$appointment->patient->full_name},\n\nYour appointment for {$appointment->service->name} on {$appointment->appointment_date->toDateString()} at {$this->time($appointment->start_time)} has been cancelled.\n\nReason: {$appointment->cancellation_reason}",
            default => '',
        };

        $this->sendPatientMail($appointment, $subject, $body);
    }

    public function rescheduled(Appointment $appointment): void
    {
        $appointment->loadMissing(['patient', 'doctor', 'service']);

        $this->sendPatientMail(
            $appointment,
            'Appointment rescheduled',
            "Hi {$appointment->patient->full_name},\n\nYour appointment for {$appointment->service->name} with {$appointment->doctor->full_name} has been rescheduled to {$appointment->appointment_date->toDateString()} at {$this->time($appointment->start_time)}.",
        );
    }

    private function sendPatientMail(Appointment $appointment, string $subject, string $body): void
    {
        if (! $appointment->patient?->email) {
            return;
        }

        $this->sendRawMail($appointment->patient->email, $subject, $body);
    }

    private function sendRawMail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($message) => $message->to($to)->subject($subject));
        } catch (Throwable $exception) {
            Log::warning('Appointment email could not be sent.', [
                'to' => $to,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function time(string $time): string
    {
        return substr($time, 0, 5);
    }
}
