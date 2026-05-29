<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorAvailabilityRequest;
use App\Http\Requests\Admin\UpdateDoctorAvailabilityRequest;
use App\Http\Resources\DoctorAvailabilityResource;
use App\Models\DoctorAvailability;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DoctorAvailabilityController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $availability = DoctorAvailability::query()
            ->with('doctor')
            ->when(request()->filled('doctor_id'), function ($query): void {
                $query->where('doctor_id', request('doctor_id'));
            })
            ->when(request()->filled('day_of_week'), function ($query): void {
                $query->where('day_of_week', request('day_of_week'));
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return DoctorAvailabilityResource::collection($availability);
    }

    public function store(StoreDoctorAvailabilityRequest $request): DoctorAvailabilityResource
    {
        $availability = DoctorAvailability::query()->create($request->validated());

        return DoctorAvailabilityResource::make($availability->load('doctor'));
    }

    public function show(DoctorAvailability $doctorAvailability): DoctorAvailabilityResource
    {
        return DoctorAvailabilityResource::make($doctorAvailability->load('doctor'));
    }

    public function update(
        UpdateDoctorAvailabilityRequest $request,
        DoctorAvailability $doctorAvailability
    ): DoctorAvailabilityResource {
        $doctorAvailability->update($request->validated());

        return DoctorAvailabilityResource::make($doctorAvailability->fresh()->load('doctor'));
    }

    public function destroy(DoctorAvailability $doctorAvailability): Response
    {
        $doctorAvailability->update(['is_active' => false]);

        return response()->noContent();
    }
}
