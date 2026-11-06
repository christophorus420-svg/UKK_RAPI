<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $query = Room::with('pic')->withCount(['events', 'complaints']);

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($sub) use ($keyword) {
                $sub->where('room_name', 'like', "%{$keyword}%")
                    ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('pic_id')) {
            $query->where('pic_id', $request->query('pic_id'));
        }

        if ($request->filled('status')) {
            $isActive = $request->query('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $rooms = $query->orderBy('room_name')->get();

        $picUsers = User::whereHas('role', fn ($q) => $q->where('role_name', 'pic'))
            ->withCount('rooms')
            ->orderBy('fullname')
            ->get();

        return view('rooms.index', compact('rooms', 'picUsers'));
    }

    public function create(): View
    {
        $picUsers = User::whereHas('role', fn ($q) => $q->where('role_name', 'pic'))
            ->withCount('rooms')
            ->orderBy('fullname')
            ->get();

        return view('rooms.create', compact('picUsers'));
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $room = Room::create($validated);

        return redirect()->route('rooms.index')
            ->with('success', "Ruangan '{$room->room_name}' berhasil ditambahkan dan didelegasikan kepada PIC terkait.");
    }

    public function edit(Room $room): View
    {
        $picUsers = User::whereHas('role', fn ($q) => $q->where('role_name', 'pic'))
            ->withCount('rooms')
            ->orderBy('fullname')
            ->get();

        return view('rooms.edit', compact('room', 'picUsers'));
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $room->update($validated);

        return redirect()->route('rooms.index')
            ->with('success', "Data ruangan '{$room->room_name}' berhasil diperbarui.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->events()->exists() || $room->complaints()->exists()) {
            return redirect()->route('rooms.index')
                ->with('error', "Ruangan '{$room->room_name}' tidak dapat dihapus permanen karena memiliki riwayat agenda peminjaman atau laporan kerusakan sarpras. Anda dapat menonaktifkan statusnya agar tidak dapat dipinjam.");
        }

        $namaRuang = $room->room_name;
        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', "Ruangan '{$namaRuang}' berhasil dihapus dari sistem.");
    }

    public function toggleStatus(Room $room): RedirectResponse
    {
        $room->is_active = !$room->is_active;
        $room->save();

        $statusText = $room->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';

        return back()->with('success', "Status ketersediaan ruangan '{$room->room_name}' berhasil {$statusText}.");
    }
}
