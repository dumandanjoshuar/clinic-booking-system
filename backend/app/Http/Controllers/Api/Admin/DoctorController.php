<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Http\Resources\DoctorResource;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        $data = $request->validated();
        $doctor = DB::transaction(function () use ($data): Doctor {
            $user = $this->createDoctorUserIfRequested($data);

            return Doctor::query()->create([
                'user_id' => $data['user_id'] ?? $user?->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? $user?->email,
                'phone' => $data['phone'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });

        return DoctorResource::make($doctor->load('user'));
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return DoctorResource::make($doctor->load('user'));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $doctor): void {
            $user = $doctor->user;

            if (($data['create_user_account'] ?? false) && ! $user) {
                $user = $this->createDoctorUserIfRequested($data);
            }

            $doctor->update([
                'user_id' => $data['user_id'] ?? $user?->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? $user?->email,
                'phone' => $data['phone'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });

        return DoctorResource::make($doctor->fresh()->load('user'));
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        if ($doctor->appointments()->exists()) {
            $doctor->update(['is_active' => false]);

            return response()->json([
                'message' => 'Doctor has appointment history, so they were deactivated instead of deleted.',
                'action' => 'deactivated',
            ]);
        }

        DB::transaction(function () use ($doctor): void {
            $user = $doctor->user;
            $doctor->delete();

            if ($user?->role === User::ROLE_DOCTOR && ! $user->doctor()->exists()) {
                $user->tokens()->delete();
                $user->delete();
            }
        });

        return response()->json([
            'message' => 'Doctor deleted successfully.',
            'action' => 'deleted',
        ]);
    }

    private function createDoctorUserIfRequested(array $data): ?User
    {
        if (! ($data['create_user_account'] ?? false)) {
            return null;
        }

        $email = $data['email'] ?? null;

        abort_if(! $email, 422, 'Email is required when creating a doctor login account.');
        abort_if(User::query()->where('email', $email)->exists(), 422, 'A user account already exists for this email.');

        return User::query()->create([
            'name' => $data['full_name'],
            'email' => $email,
            'password' => Hash::make($data['temporary_password']),
            'role' => User::ROLE_DOCTOR,
            'must_change_password' => true,
        ]);
    }

    public function deactivate(Doctor $doctor): JsonResponse
    {
        $doctor->update(['is_active' => false]);

        return response()->json([
            'message' => 'Doctor deactivated successfully.',
            'action' => 'deactivated',
        ]);
    }
}
