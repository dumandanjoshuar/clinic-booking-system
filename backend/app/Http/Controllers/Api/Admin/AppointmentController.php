<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Appointment\RescheduleAppointmentRequest;
use App\Http\Requests\Admin\Appointment\StatusActionRequest;
use App\Http\Requests\Admin\Appointment\UpdateAppointmentNotesRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\AppointmentWorkflowService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $appointments = Appointment::query()
            ->with(['patient', 'doctor', 'service'])
            ->when(request()->filled('date'), fn ($query) => $query->whereDate('appointment_date', request('date')))
            ->when(request()->filled('status'), fn ($query) => $query->where('status', request('status')))
            ->when(request()->filled('doctor_id'), fn ($query) => $query->where('doctor_id', request('doctor_id')))
            ->when(request()->filled('service_id'), fn ($query) => $query->where('service_id', request('service_id')))
            ->orderByDesc('appointment_date')
            ->orderBy('start_time')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        return AppointmentResource::make(
            $appointment->load(['patient', 'doctor', 'service', 'statusLogs.changedBy']),
        );
    }

    public function updateNotes(UpdateAppointmentNotesRequest $request, Appointment $appointment): AppointmentResource
    {
        $appointment->update($request->validated());

        return AppointmentResource::make($appointment->fresh(['patient', 'doctor', 'service']));
    }

    public function approve(
        StatusActionRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_CONFIRMED, $request->user()->id, $data['notes'] ?? null),
        );
    }

    public function reject(
        StatusActionRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_REJECTED, $request->user()->id, $data['notes'] ?? null),
        );
    }

    public function cancel(
        StatusActionRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_CANCELLED, $request->user()->id, $data['notes'] ?? null),
        );
    }

    public function complete(
        StatusActionRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_COMPLETED, $request->user()->id, $data['notes'] ?? null),
        );
    }

    public function noShow(
        StatusActionRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();

        return AppointmentResource::make(
            $workflowService->changeStatus($appointment, Appointment::STATUS_NO_SHOW, $request->user()->id, $data['notes'] ?? null),
        );
    }

    public function reschedule(
        RescheduleAppointmentRequest $request,
        Appointment $appointment,
        AppointmentWorkflowService $workflowService,
    ): AppointmentResource {
        $data = $request->validated();
        $doctor = Doctor::query()->where('is_active', true)->findOrFail($data['doctor_id']);

        return AppointmentResource::make(
            $workflowService->reschedule(
                $appointment->load('service'),
                $doctor,
                $data['appointment_date'],
                $data['start_time'],
                $request->user()->id,
                $data['notes'] ?? null,
            ),
        );
    }
}
