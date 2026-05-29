<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'counts' => [
                'active_services' => Service::query()->where('is_active', true)->count(),
                'active_doctors' => Doctor::query()->where('is_active', true)->count(),
                'active_availability_rules' => DoctorAvailability::query()->where('is_active', true)->count(),
            ],
        ]);
    }
}
