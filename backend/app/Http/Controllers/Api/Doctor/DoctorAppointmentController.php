<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\CompleteAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorAppointmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $doctor = $this->doctorFor($request);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'service'])
            ->where('doctor_id', $doctor->id)
            ->when($request->filled('date'), fn ($query) => $query->whereDate('appointment_date', $request->query('date')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function show(Request $request, Appointment $appointment): AppointmentResource
    {
        $doctor = $this->doctorFor($request);
        abort_unless($appointment->doctor_id === $doctor->id, 403);

        return AppointmentResource::make($appointment->load(['patient', 'doctor', 'service', 'statusLogs.changedBy']));
    }

    public function complete(
        CompleteAppointmentRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $doctor = $this->doctorFor($request);
        abort_unless($appointment->doctor_id === $doctor->id, 403);
        abort_unless($appointment->status === Appointment::STATUS_CONFIRMED, 422, 'Only confirmed appointments can be completed.');

        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_COMPLETED, $request->user()->id, $data['notes'] ?? null),
        );
    }

    private function doctorFor(Request $request): Doctor
    {
        return $request->user()->doctor ?? abort(403, 'No doctor profile is linked to this user.');
    }
}
