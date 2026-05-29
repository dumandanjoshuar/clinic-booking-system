<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $doctor = $this->doctorFor($request);

        return response()->json([
            'doctor' => [
                'id' => $doctor->id,
                'full_name' => $doctor->full_name,
                'specialization' => $doctor->specialization,
            ],
            'counts' => [
                'today_confirmed' => Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('status', Appointment::STATUS_CONFIRMED)
                    ->whereDate('appointment_date', now()->toDateString())
                    ->count(),
                'upcoming_confirmed' => Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('status', Appointment::STATUS_CONFIRMED)
                    ->whereDate('appointment_date', '>=', now()->toDateString())
                    ->count(),
                'completed_total' => Appointment::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('status', Appointment::STATUS_COMPLETED)
                    ->count(),
            ],
        ]);
    }

    private function doctorFor(Request $request): Doctor
    {
        return $request->user()->doctor ?? abort(403, 'No doctor profile is linked to this user.');
    }
}
