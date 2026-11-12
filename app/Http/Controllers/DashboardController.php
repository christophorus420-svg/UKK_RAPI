<?php

namespace App\Http\Controllers;

use App\Models\BookingSession;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $userRole = $user->role->role_name;
        $today = Carbon::today();

        $totalRuangan = Room::count();
        $ruanganAktif = Room::where('is_active', true)->count();
        $agendaHariIni = Event::whereDate('date', $today)->where('status', 'approved')->count();
        $komplainBelumSelesai = Complaint::where('status_repair', '!=', 'Sudah Diperbaiki')->count();

        $data = [
            'totalRuangan' => $totalRuangan,
            'ruanganAktif' => $ruanganAktif,
            'agendaHariIni' => $agendaHariIni,
            'komplainBelumSelesai' => $komplainBelumSelesai,
            'userRole' => $userRole,
        ];

        if ($userRole === 'admin') {
            $todayStr = $today->toDateString();
            $currentTime = Carbon::now()->format('H:i:s');

            // REVISI ADMIN 3: Widget Ruang Kosong Saat Ini (Real-Time Availability)
            $activeRooms = Room::with('pic')->where('is_active', true)->orderBy('room_name')->get();

            $ongoingEvents = Event::with(['user', 'session', 'room'])
                ->where('status', 'approved')
                ->where('date', '<=', $todayStr)
                ->whereRaw('COALESCE(end_date, date) >= ?', [$todayStr])
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime)
                ->get()
                ->keyBy('room_id');

            $upcomingTodayEvents = Event::with(['user', 'session'])
                ->where('status', 'approved')
                ->where('date', '<=', $todayStr)
                ->whereRaw('COALESCE(end_date, date) >= ?', [$todayStr])
                ->where('start_time', '>', $currentTime)
                ->orderBy('start_time')
                ->get()
                ->groupBy('room_id');

            $statusRuangList = $activeRooms->map(function ($room) use ($ongoingEvents, $upcomingTodayEvents) {
                $isOccupied = $ongoingEvents->has($room->id);
                $activeEvent = $isOccupied ? $ongoingEvents->get($room->id) : null;
                $nextEvent = $upcomingTodayEvents->get($room->id)?->first();

                return (object) [
                    'room' => $room,
                    'is_occupied' => $isOccupied,
                    'is_empty' => !$isOccupied,
                    'active_event' => $activeEvent,
                    'next_event' => $nextEvent,
                ];
            });

            $data['statusRuangList'] = $statusRuangList;
            $data['ruangKosongList'] = $statusRuangList->filter(fn ($r) => $r->is_empty);
            $data['ruangTerpakaiList'] = $statusRuangList->filter(fn ($r) => $r->is_occupied);

            $data['daftarPengajuanPending'] = Event::with(['room', 'user'])
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get();

            $data['daftarKomplainPerluEskalasi'] = Complaint::with(['room', 'handler', 'reporter'])
                ->where('status_repair', 'Belum Diperbaiki')
                ->where('reported_at', '<=', Carbon::now()->subDays(2))
                ->latest()
                ->take(5)
                ->get();

            $data['daftarAgendaDipindahkan'] = Event::with(['room', 'user', 'approver'])
                ->where('status', 'approved')
                ->whereNotNull('move_reason')
                ->latest('updated_at')
                ->take(5)
                ->get();

            $data['agendaMendatang'] = Event::with(['room', 'user', 'approver'])
                ->where('status', 'approved')
                ->whereRaw('COALESCE(end_date, date) >= ?', [$todayStr])
                ->orderBy('date')
                ->orderBy('start_time')
                ->take(5)
                ->get();

            $data['daftarSesi'] = BookingSession::orderBy('start_time')->get();
        } elseif ($userRole === 'pic') {
            $ruanganDikelola = Room::where('pic_id', $user->id)->get();
            $idRuanganDikelola = $ruanganDikelola->pluck('id');

            $data['ruanganDikelola'] = $ruanganDikelola;
            $data['daftarKomplainPic'] = Complaint::with(['room', 'reporter'])
                ->whereIn('room_id', $idRuanganDikelola)
                ->latest()
                ->take(5)
                ->get();
            $data['totalKomplainTanggungJawab'] = Complaint::whereIn('room_id', $idRuanganDikelola)
                ->where('status_repair', '!=', 'Sudah Diperbaiki')
                ->count();

            // REVISI PIC 3: Jadwal agenda terdekat dari hari ini pada ruangan PIC
            $data['jadwalRuanganPic'] = Event::with(['room', 'user', 'session'])
                ->whereIn('room_id', $idRuanganDikelola)
                ->where('status', 'approved')
                ->orderByRaw('CASE WHEN COALESCE(end_date, date) >= ? THEN 0 ELSE 1 END', [$today->toDateString()])
                ->orderByRaw('ABS(DATEDIFF(date, ?)) ASC', [$today->toDateString()])
                ->orderBy('start_time', 'asc')
                ->take(6)
                ->get();
        } elseif ($userRole === 'guru') {
            $data['agendaSaya'] = Event::with(['room', 'session'])
                ->where('pic_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            $data['pengaduanSaya'] = Complaint::with('room')
                ->where('reporter_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            // Notifikasi dinamis jika ada ruangan yang dipindahkan oleh Waka
            $data['agendaDipindahkan'] = Event::with(['room', 'approver'])
                ->where('pic_id', $user->id)
                ->whereNotNull('move_reason')
                ->whereDate('date', '>=', $today)
                ->latest('updated_at')
                ->take(3)
                ->get();
        } else {
            $data['agendaPublik'] = Event::with('room')
                ->where('status', 'approved')
                ->whereDate('date', '>=', $today)
                ->orderBy('date')
                ->orderBy('start_time')
                ->take(6)
                ->get();
        }

        return view('dashboard.index', $data);
    }
}
