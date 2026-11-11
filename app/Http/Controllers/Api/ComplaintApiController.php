<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComplaintApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Otomatis bersihkan pengaduan yang sudah selesai diperbaiki lebih dari 24 jam
        Complaint::purgeResolvedOverdue();

        $user = Auth::user();
        $query = Complaint::with(['room.pic', 'reporter', 'event']);

        if ($user && ! $user->isAdmin()) {
            if ($user->isPic()) {
                $roomIds = Room::where('pic_id', $user->id)->pluck('id');
                $query->where(function ($sub) use ($roomIds, $user) {
                    $sub->whereIn('room_id', $roomIds)
                        ->orWhere('reporter_id', $user->id);
                });
            } else {
                $query->where('reporter_id', $user->id);
            }
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('status')) {
            $query->where('status_repair', $request->query('status'));
        }

        $complaints = $query->latest()->get();

        return response()->json([
            'success' => true,
            'total' => $complaints->count(),
            'data' => $complaints->map(function ($c) {
                return [
                    'id' => $c->id,
                    'room' => [
                        'id' => $c->room?->id,
                        'name' => $c->room?->room_name,
                        'location' => $c->room?->location,
                        'pic_name' => $c->room?->pic?->fullname,
                        'pic_whatsapp' => $c->room?->pic?->whatsapp_number,
                    ],
                    'reporter' => [
                        'id' => $c->reporter?->id,
                        'fullname' => $c->reporter?->fullname,
                    ],
                    'description' => $c->description,
                    'status_repair' => $c->status_repair,
                    'status' => $c->status_repair,
                    'photo_url' => $c->photo_url,
                    'is_overdue' => $c->isOverdue(),
                    'repaired_at' => $c->repaired_at?->toIso8601String(),
                    'auto_delete_remaining' => $c->auto_delete_remaining,
                    'can_delete' => $user ? ($user->isAdmin() || (int) $c->reporter_id === (int) $user->id || (int) ($c->room?->pic_id) === (int) $user->id) : false,
                    'created_at' => $c->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'description' => ['required', 'string', 'min:10'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'complaint_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $photoPath = $file->storeAs('complaints', $filename, 'public');
        }

        $complaint = Complaint::create([
            'room_id' => $validated['room_id'],
            'reporter_id' => $user->id,
            'description' => $validated['description'],
            'photo_path' => $photoPath,
            'status_repair' => 'Belum Diperbaiki',
            'reported_at' => now(),
        ]);

        $complaint->load(['room.pic', 'reporter']);

        return response()->json([
            'success' => true,
            'message' => 'Laporan kerusakan berhasil dikirim.',
            'data' => [
                'id' => $complaint->id,
                'room' => [
                    'id' => $complaint->room?->id,
                    'name' => $complaint->room?->room_name,
                    'location' => $complaint->room?->location,
                    'pic_name' => $complaint->room?->pic?->fullname,
                    'pic_whatsapp' => $complaint->room?->pic?->whatsapp_number,
                ],
                'reporter' => [
                    'id' => $complaint->reporter?->id,
                    'fullname' => $complaint->reporter?->fullname,
                ],
                'description' => $complaint->description,
                'status_repair' => $complaint->status_repair,
                'status' => $complaint->status_repair,
                'photo_url' => $complaint->photo_url,
                'photo_path' => $complaint->photo_path,
                'is_overdue' => $complaint->isOverdue(),
                'repaired_at' => $complaint->repaired_at?->toIso8601String(),
                'auto_delete_remaining' => $complaint->auto_delete_remaining,
                'can_delete' => true,
                'created_at' => $complaint->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $complaint = Complaint::with('room')->findOrFail($id);

        // Admin tidak dapat mengubah status secara langsung
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Administrator tidak dapat mengubah status perbaikan secara langsung. Hak pembaruan hanya dimiliki oleh staf PIC ruangan terkait.',
            ], 403);
        }

        // Hanya PIC ruangan terkait yang berhak memperbarui status
        if ($complaint->room->pic_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Hanya PIC penanggung jawab ruangan yang berhak mengubah status perbaikan.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'])],
        ]);

        $dataToUpdate = [
            'status_repair' => $validated['status'],
            'handler_id' => $user->id,
        ];

        if ($validated['status'] === 'Sudah Diperbaiki') {
            $dataToUpdate['repaired_at'] = now();
        }

        $complaint->update($dataToUpdate);
        $complaint->load(['room.pic', 'reporter']);

        $message = 'Status siklus perbaikan berhasil diperbarui ke: ' . $validated['status'];
        if ($validated['status'] === 'Sudah Diperbaiki') {
            $message .= '. Laporan ini akan otomatis dihapus oleh sistem setelah 24 jam.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $complaint,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $complaint = Complaint::with('room')->findOrFail($id);

        $isPicRuang = (int) ($complaint->room?->pic_id) === (int) $user->id;
        $isReporter = (int) $complaint->reporter_id === (int) $user->id;

        if (!$user->isAdmin() && !$isReporter && !$isPicRuang) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus laporan kerusakan ini.',
            ], 403);
        }

        if ($complaint->photo_path) {
            Storage::disk('public')->delete($complaint->photo_path);
        }

        $complaint->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan pengaduan berhasil dihapus dari sistem.',
        ]);
    }
}
