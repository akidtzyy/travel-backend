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
        $query = CarRental::query();

        if ($request->has('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->boolean('is_available', true)) {
            $query->where('is_available', true);
        }

        $cars = $query->orderBy('price', 'asc')->get();

        $mapped = $cars->map(function ($car) {
            return [
                'id'            => $car->id,
                'name'          => $car->name,
                'type'          => $car->type,
                'seats'         => $car->capacity, // map capacity to seats
                'price'         => (float) $car->price,
                'is_available'  => (bool) $car->is_available,
                'image_url'     => $car->image_url,
                'duration_desc' => 'Per 24 Jam', // default fallback for frontend compat
                'category'      => $car->type === 'self_drive' ? 'Lepas Kunci' : 'Dengan Sopir',
                'features'      => ['AC', 'Audio System', 'Asuransi', 'Bersih & Nyaman'], // default fallback
            ];
        });

        return response()->json(['data' => $mapped]);
    }

    /**
     * POST /api/v1/car-rentals
     * Admin: add a new car to the fleet.
     */
    public function store(StoreCarRentalRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Map seats/features if passed from frontend
        $capacity = $request->input('seats') ?? $validated['capacity'] ?? 4;

        $car = CarRental::create([
            'name'         => $validated['name'],
            'type'         => $validated['type'],
            'capacity'     => $capacity,
            'price'        => $validated['price'],
            'is_available' => $validated['is_available'] ?? true,
            'image_url'    => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Car rental added successfully.',
            'data'    => $car,
        ], 201);
    }

    /**
     * PUT/PATCH /api/v1/car-rentals/{id}
     * Admin: update a car rental.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $car = CarRental::findOrFail($id);

        $validated = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'type'         => ['sometimes', 'string', 'max:100'],
            'seats'        => ['sometimes', 'integer', 'min:1'],
            'capacity'     => ['sometimes', 'integer', 'min:1'],
            'price'        => ['sometimes', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
            'image_url'    => ['nullable', 'string'],
        ]);

        $updateData = [];
        if (isset($validated['name']))         $updateData['name'] = $validated['name'];
        if (isset($validated['type']))         $updateData['type'] = $validated['type'];
        if (isset($validated['seats']))        $updateData['capacity'] = $validated['seats'];
        if (isset($validated['capacity']))     $updateData['capacity'] = $validated['capacity'];
        if (isset($validated['price']))        $updateData['price'] = $validated['price'];
        if (isset($validated['is_available'])) $updateData['is_available'] = $validated['is_available'];
        if (array_key_exists('image_url', $validated)) $updateData['image_url'] = $validated['image_url'];

        $car->update($updateData);

        return response()->json([
            'message' => 'Car rental updated successfully.',
            'data'    => $car,
        ]);
    }

    /**
     * DELETE /api/v1/car-rentals/{id}
     * Admin: delete a car rental.
     */
    public function destroy($id): JsonResponse
    {
        $car = CarRental::findOrFail($id);
        $car->delete();

        return response()->json([
            'message' => 'Car rental deleted successfully.'
        ]);
    }
}

