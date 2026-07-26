<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Services\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(private BookingPaymentService $paymentService)
    {
    }

    /**
     * POST /api/v1/bookings
     * Create a new booking with server-side price verification.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $booking = DB::transaction(function () use ($data, $request) {
                // 1. Server-side price calculation — do NOT trust client input
                $quantity  = $data['quantity'] ?? 1;
                $totalPrice = $this->paymentService->calculatePrice(
                    $data['booking_type'],
                    $data['item_id'],
                    $quantity
                );

                // 2. Find or create customer record
                $customer = Customer::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'                        => $data['name'],
                        'phone'                       => preg_replace('/\D/', '', $data['phone']),
                        'nationality_type'            => $data['nationality_type'],
                        'identity_type'               => $data['identity_type'],
                        'identity_number'             => $data['identity_number'] ?? null,
                        'identity_verification_status'=> 'UNVERIFIED',
                        'user_id'                     => $request->user()?->id,
                    ]
                );

                // If customer already exists, keep their data fresh
                if (! $customer->wasRecentlyCreated) {
                    $customer->update([
                        'name'  => $data['name'],
                        'phone' => preg_replace('/\D/', '', $data['phone']),
                    ]);
                }

                // 3. Build booking record
                return Booking::create([
                    'booking_code'      => Booking::generateBookingCode(),
                    'customer_id'       => $customer->id,
                    'booking_type'      => $data['booking_type'],
                    'item_name'         => $data['item_name'] ?? "Item #{$data['item_id']}",
                    'date'              => $data['date'],
                    'duration'          => $data['duration'],
                    'notes'             => $data['notes'] ?? null,
                    'total_price'       => $totalPrice,
                    'payment_type'      => $data['payment_type'],
                    'amount_paid'       => 0,
                    'remaining_balance' => $totalPrice,
                    'status'            => 'pending',
                    'payment_status'    => 'unpaid',
                    'expiry_time'       => now()->addMinutes(30),
                ]);
            });

            return response()->json([
                'message' => 'Booking berhasil dibuat.',
                'data'    => $booking->load('customer'),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Booking creation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat booking: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/bookings
     * Admin: list all bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with('customer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $bookings
        ]);
    }

    /**
     * PUT/PATCH /api/v1/bookings/{id}
     * Admin: update booking details or status.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        $data = $request->validate([
            'status'                => ['sometimes', 'string', 'in:pending,confirmed,paid,completed,cancelled,rescheduled,expired'],
            'payment_status'        => ['sometimes', 'string'],
            'date'                  => ['sometimes', 'date'],
            'end_date'              => ['sometimes', 'date', 'nullable'],
            'duration'              => ['sometimes', 'string'],
            'notes'                 => ['sometimes', 'string', 'nullable'],
            'original_booking_date' => ['sometimes', 'date', 'nullable'],
            'reschedule_notes'      => ['sometimes', 'string', 'nullable'],
        ]);

        $booking->update($data);

        return response()->json([
            'message' => 'Booking berhasil diperbarui.',
            'data'    => $booking->load('customer')
        ]);
    }

    /**
     * DELETE /api/v1/bookings/{id}
     * Admin: delete a booking.
     */
    public function destroy($id): JsonResponse
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json([
            'message' => 'Booking berhasil dihapus.'
        ]);
    }
}

