<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class AvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
