<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'patient.full_name' => ['required', 'string', 'max:255'],
            'patient.email' => ['required', 'email', 'max:255'],
            'patient.phone' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
