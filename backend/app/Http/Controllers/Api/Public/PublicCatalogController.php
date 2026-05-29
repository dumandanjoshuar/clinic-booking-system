<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Http\Resources\ServiceResource;
use App\Models\Doctor;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicCatalogController extends Controller
{
    public function services(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            Service::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        );
    }

    public function doctors(): AnonymousResourceCollection
    {
        return DoctorResource::collection(
            Doctor::query()
                ->where('is_active', true)
                ->orderBy('full_name')
                ->get(),
        );
    }
}
