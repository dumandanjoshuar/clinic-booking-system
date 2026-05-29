<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $services = Service::query()
            ->when(request()->filled('search'), function ($query): void {
                $query->where('name', 'like', '%'.request('search').'%');
            })
            ->when(request()->has('is_active'), function ($query): void {
                $query->where('is_active', filter_var(request('is_active'), FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->get();

        return ServiceResource::collection($services);
    }

    public function store(StoreServiceRequest $request): ServiceResource
    {
        $service = Service::query()->create($request->validated());

        return ServiceResource::make($service);
    }

    public function show(Service $service): ServiceResource
    {
        return ServiceResource::make($service);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $service->update($request->validated());

        return ServiceResource::make($service->fresh());
    }

    public function destroy(Service $service): Response
    {
        $service->update(['is_active' => false]);

        return response()->noContent();
    }
}
