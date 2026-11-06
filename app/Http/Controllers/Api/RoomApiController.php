<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $rooms = Room::with('pic')
            ->where('is_active', true)
            ->orderBy('room_name')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $rooms->count(),
            'data' => $rooms->map(function ($room) {
                return [
                    'id' => $room->id,
                    'room_name' => $room->room_name,
                    'location' => $room->location,
                    'capacity' => $room->capacity,
                    'pic' => [
                        'id' => $room->pic?->id,
                        'fullname' => $room->pic?->fullname,
                        'phone' => $room->pic?->phone_number,
                        'whatsapp' => $room->pic?->whatsapp_number,
                    ],
                ];
            }),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $room = Room::with('pic')->findOrFail($id);

        $upcomingEvents = $room->events()
            ->where('date', '>=', Carbon::today()->toDateString())
            ->where('status', 'approved')
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $room->id,
                'room_name' => $room->room_name,
                'location' => $room->location,
                'capacity' => $room->capacity,
                'is_active' => (bool) $room->is_active,
                'pic' => [
                    'id' => $room->pic?->id,
                    'fullname' => $room->pic?->fullname,
                    'phone' => $room->pic?->phone_number,
                    'whatsapp' => $room->pic?->whatsapp_number,
                ],
                'upcoming_events' => $upcomingEvents,
            ],
        ]);
    }
}
