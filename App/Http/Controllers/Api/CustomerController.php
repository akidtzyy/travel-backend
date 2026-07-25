<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api/v1/customers/search?query=
     * Admin: autocomplete search for customers by name, phone, or email.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->string('query')->trim();

        if ($query->isEmpty() || $query->length() < 2) {
            return response()->json(['data' => []]);
        }

        $customers = Customer::withTrashed(false)
            ->select(['id', 'name', 'email', 'phone', 'nationality_type', 'identity_verification_status', 'total_bookings'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json(['data' => $customers]);
    }
}
