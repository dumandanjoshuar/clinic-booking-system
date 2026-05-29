<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AvailableSlotsRequest;
use App\Models\Doctor;
use App\Models\Service;
use App\Services\AppointmentSlotService;
use Illuminate\Http\JsonResponse;

class AvailableSlotController extends Controller
{
    public function __invoke(AvailableSlotsRequest $request, AppointmentSlotService $slotService): JsonResponse
    {
        $data = $request->validated();
        $service = Service::query()->where('is_active', true)->findOrFail($data['service_id']);
        $doctor = isset($data['doctor_id'])
            ? Doctor::query()->where('is_active', true)->findOrFail($data['doctor_id'])
            : null;

        return response()->json([
            'data' => $slotService->availableSlots($service, $data['date'], $doctor),
        ]);
    }
}
