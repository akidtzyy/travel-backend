<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CarRental;
use App\Http\Requests\StoreCarRentalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarRentalController extends Controller
{
    /**
     * GET /api/v1/car-rentals
     * Public: list cars, optionally filtered by type and availability.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CarRental::query()
            ->select(['id', 'name', 'type', 'capacity', 'price', 'is_available', 'image_url']);

        if ($request->has('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->boolean('is_available', true)) {
            $query->where('is_available', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    /**
     * POST /api/v1/car-rentals
     * Admin: add a new car to the fleet.
     */
    public function store(StoreCarRentalRequest $request): JsonResponse
    {
        $car = CarRental::create($request->validated());

        return response()->json([
            'message' => 'Car rental added successfully.',
            'data'    => $car,
        ], 201);
    }
}
