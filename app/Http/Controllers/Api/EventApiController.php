<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['room.pic', 'user.role', 'approver']);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        if ($request->filled('month') && $request->filled('year')) {
            $m = (int) $request->query('month');
            $y = (int) $request->query('year');
            $startOfMonth = Carbon::createFromDate($y, $m, 1)->startOfMonth()->toDateString();
            $endOfMonth = Carbon::createFromDate($y, $m, 1)->endOfMonth()->toDateString();
            $query->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->where('date', '<=', $endOfMonth)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startOfMonth]);
            });
        } elseif ($request->filled('month')) {
            $query->whereMonth('date', $request->query('month'));
        } elseif ($request->filled('year')) {
            $query->whereYear('date', $request->query('year'));
        }

        // Jika ada filter status eksplisit
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            // Default hanya tampilkan agenda yang sudah disetujui resmi
            $query->where('status', 'approved');
        }

        $events = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json([
            'success' => true,
            'total' => $events->count(),
            'data' => $events->map(function ($e) {
                return [
                    'id' => $e->id,
                    'event_name' => $e->event_name,
                    'date' => $e->date->toDateString(),
                    'end_date' => $e->end_date ? $e->end_date->toDateString() : null,
                    'date_formatted' => $e->date->translatedFormat('l, d F Y'),
                    'start_time' => substr($e->start_time, 0, 5),
                    'end_time' => substr($e->end_time, 0, 5),
                    'status' => $e->status,
                    'room' => [
                        'id' => $e->room?->id,
                        'name' => $e->room?->room_name,
                        'location' => $e->room?->location,
                        'capacity' => $e->room?->capacity,
                    ],
                    'organizer' => [
                        'id' => $e->user?->id,
                        'fullname' => $e->user?->fullname,
                        'role' => $e->user?->role?->role_name,
                    ],
                    'move_reason' => $e->move_reason,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Autentikasi diperlukan untuk mengajukan agenda.',
            ], 401);
        }

        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'event_name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $roomId = $validated['room_id'];
        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // Cek bentrok jadwal (Conflict Prevention Logic)
        $conflicts = Event::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->get();

        if ($conflicts->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal ruangan bentrok dengan agenda lain yang sudah disetujui atau diajukan.',
                'conflicts' => $conflicts->map(function ($c) {
                    return [
                        'event_name' => $c->event_name,
                        'time' => substr($c->start_time, 0, 5) . ' - ' . substr($c->end_time, 0, 5),
                        'status' => $c->status,
                    ];
                }),
            ], 422);
        }

        // Tentukan status awal: Admin langsung approved, selain admin statusnya pending
        $status = $user->isAdmin() ? 'approved' : 'pending';
        $approvedBy = $user->isAdmin() ? $user->id : null;

        $event = Event::create([
            'room_id' => $roomId,
            'pic_id' => $user->id,
            'event_name' => $validated['event_name'],
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'approved_by' => $approvedBy,
        ]);

        $event->load(['room', 'user']);

        return response()->json([
            'success' => true,
            'message' => $user->isAdmin()
                ? 'Agenda resmi berhasil dibuat dan langsung disetujui.'
                : 'Pengajuan agenda berhasil dikirim dan menunggu persetujuan Waka/Admin.',
            'data' => $event,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = Event::with(['room.pic', 'user.role', 'approver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $event->id,
                'event_name' => $event->event_name,
                'date' => $event->date->toDateString(),
                'date_formatted' => $event->date->translatedFormat('l, d F Y'),
                'start_time' => substr($event->start_time, 0, 5),
                'end_time' => substr($event->end_time, 0, 5),
                'status' => $event->status,
                'room' => [
                    'id' => $event->room?->id,
                    'name' => $event->room?->room_name,
                    'location' => $event->room?->location,
                    'capacity' => $event->room?->capacity,
                ],
                'organizer' => [
                    'id' => $event->user?->id,
                    'fullname' => $event->user?->fullname,
                    'role' => $event->user?->role?->role_name,
                ],
                'approver' => $event->approver ? $event->approver->fullname : null,
                'move_reason' => $event->move_reason,
            ],
        ]);
    }
}
