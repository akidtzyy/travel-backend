<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TourPackage;
use App\Http\Requests\StoreTourPackageRequest;
use Illuminate\Http\JsonResponse;

class TourPackageController extends Controller
{
    /**
     * GET /api/v1/tour-packages
     * Public: list all active packages.
     */
    public function index(): JsonResponse
    {
        $packages = TourPackage::active()
            ->select(['id', 'name', 'description', 'duration', 'price', 'highlights', 'included', 'category', 'image_url'])
            ->get();

        return response()->json(['data' => $packages]);
    }

    /**
     * POST /api/v1/tour-packages
     * Admin: create a new tour package.
     */
    public function store(StoreTourPackageRequest $request): JsonResponse
    {
        $package = TourPackage::create($request->validated());

        return response()->json([
            'message' => 'Tour package created successfully.',
            'data'    => $package,
        ], 201);
    }
}
