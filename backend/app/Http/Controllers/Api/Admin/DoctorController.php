<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DoctorController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $doctors = Doctor::query()
            ->with('user')
            ->when(request()->filled('search'), function ($query): void {
                $search = '%'.request('search').'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('specialization', 'like', $search);
                });
            })
            ->when(request()->has('is_active'), function ($query): void {
                $query->where('is_active', filter_var(request('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->get();

        return DoctorResource::collection($doctors);
    }

    public function store(StoreDoctorRequest $request): DoctorResource
    {
        $doctor = Doctor::query()->create($request->validated());

        return DoctorResource::make($doctor->load('user'));
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return DoctorResource::make($doctor->load('user'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $doctor->update($request->validated());

        return DoctorResource::make($doctor->fresh()->load('user'));
    }

    public function destroy(Doctor $doctor): Response
    {
        $doctor->update(['is_active' => false]);

        return response()->noContent();
    }
}
