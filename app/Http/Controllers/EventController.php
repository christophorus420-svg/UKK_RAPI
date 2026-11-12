<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRoomRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\BookingSession;
use App\Models\Event;
use App\Models\Room;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');

        // REVISI GURU 1: Ambil data untuk 3 bagian agenda utama
        // 1. Agenda Terkini (Sedang berlangsung saat ini)
        $agendaTerkini = Event::with(['room.pic', 'user', 'session'])
            ->where('status', 'approved')
            ->where('date', '<=', $today)
            ->whereRaw('COALESCE(end_date, date) >= ?', [$today])
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->orderBy('end_time', 'asc')
            ->get();

        // 2. Agenda Hari Ini (Semua kegiatan pada hari ini)
        $agendaHariIni = Event::with(['room.pic', 'user', 'session'])
            ->where('date', '<=', $today)
            ->whereRaw('COALESCE(end_date, date) >= ?', [$today])
            ->orderBy('start_time', 'asc')
            ->get();

        // 3. Agenda Yang Akan Datang (Mulai besok ke depan)
        $agendaAkanDatang = Event::with(['room.pic', 'user', 'session'])
            ->where('date', '>', $today)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(10)
            ->get();

        // Query tabel utama dengan filter & pagination
        $query = Event::with(['room.pic', 'user', 'approver', 'session']);

        if ($request->filled('q')) {
            $keyword = $request->query('q');
            $query->where(function ($sub) use ($keyword) {
                $sub->where('event_name', 'like', "%{$keyword}%")
                    ->orWhereHas('user', fn ($u) => $u->where('fullname', 'like', "%{$keyword}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('room_name', 'like', "%{$keyword}%"));
            });
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->query('room_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('date')) {
            $filterDate = $request->query('date');
            $query->where(function ($q) use ($filterDate) {
                $q->where('date', '<=', $filterDate)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$filterDate]);
            });
        }

        // REVISI PIC 3: Urutkan jadwal berdasarkan tanggal terdekat dari hari ini
        $events = $query->orderByRaw('CASE WHEN COALESCE(end_date, date) >= ? THEN 0 ELSE 1 END', [$today])
            ->orderByRaw('ABS(DATEDIFF(date, ?)) ASC', [$today])
            ->orderBy('start_time', 'asc')
            ->paginate(15)
            ->withQueryString();

        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();

        return view('events.index', compact(
            'events',
            'rooms',
            'agendaTerkini',
            'agendaHariIni',
            'agendaAkanDatang'
        ));
    }

    public function create(): View
    {
        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();
        $sessions = BookingSession::where('is_active', true)->orderBy('start_time')->get();
        return view('events.create', compact('rooms', 'sessions'));
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $status = $user->isAdmin() ? 'approved' : 'pending';
        $approvedBy = $user->isAdmin() ? $user->id : null;

        $session = $request->filled('session_id') ? BookingSession::find($request->session_id) : null;
        $startTime = $session ? substr($session->start_time, 0, 5) : $request->start_time;
        $endTime = $session ? substr($session->end_time, 0, 5) : $request->end_time;
        $endDate = $request->filled('end_date') ? $request->end_date : null;

        $event = Event::create([
            'room_id' => $request->room_id,
            'session_id' => $session?->id,
            'pic_id' => $user->id,
            'event_name' => $request->event_name,
            'date' => $request->date,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'approved_by' => $approvedBy,
            'move_reason' => null,
        ]);

        if ($status === 'approved') {
            return redirect()->route('events.index')
                ->with('success', "Agenda '{$event->event_name}' berhasil dijadwalkan dan langsung disetujui oleh Waka.");
        }

        return redirect()->route('events.index')
            ->with('success', "Pengajuan agenda '{$event->event_name}' berhasil dikirim dan sedang menunggu persetujuan Waka Sarpras.");
    }

    public function approve(Event $event): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Waka Sarpras / Admin yang berwenang menyetujui agenda.');
        }

        $startDate = $event->date->toDateString();
        $endDate = ($event->end_date ?? $event->date)->toDateString();

        // Validasi tabrakan ulang terhadap agenda lain yang sudah disetujui (mencegah race condition)
        $conflictingEvent = Event::where('room_id', $event->room_id)
            ->where('id', '!=', $event->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('date', '<=', $endDate)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate]);
            })
            ->where(function ($q) use ($event) {
                if ($event->session_id) {
                    $q->where('session_id', $event->session_id)
                      ->orWhere(function ($sub) use ($event) {
                          $sub->where('start_time', '<', $event->end_time)
                              ->where('end_time', '>', $event->start_time);
                      });
                } else {
                    $q->where('start_time', '<', $event->end_time)
                      ->where('end_time', '>', $event->start_time);
                }
            })
            ->first();

        if ($conflictingEvent) {
            $jamMulai = substr($conflictingEvent->start_time, 0, 5);
            $jamSelesai = substr($conflictingEvent->end_time, 0, 5);
            $tanggalLabel = $conflictingEvent->formatted_date_range;

            return back()->with('error', "Gagal menyetujui: Terjadi tabrakan jadwal dengan agenda '{$conflictingEvent->event_name}' ({$tanggalLabel}, {$jamMulai} - {$jamSelesai} WIB).");
        }

        $event->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', "Agenda '{$event->event_name}' telah disetujui.");
    }

    public function reject(Request $request, Event $event): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Waka Sarpras / Admin yang berwenang menolak agenda.');
        }

        $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan pengajuan peminjaman wajib diisi.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 3 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $event->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'alasan_penolakan' => $request->alasan_penolakan,
        ]);

        return back()->with('info', "Pengajuan agenda '{$event->event_name}' telah ditolak dengan catatan: \"{$request->alasan_penolakan}\".");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $user = Auth::user();

        // Hanya pemohon agenda itu sendiri (jika masih pending) atau Admin yang boleh membatalkan
        if (!$user->isAdmin() && ($event->pic_id !== $user->id || $event->status === 'approved')) {
            return back()->with('error', 'Anda tidak memiliki hak untuk membatalkan agenda ini.');
        }

        $namaEvent = $event->event_name;
        $event->delete();

        return back()->with('success', "Agenda '{$namaEvent}' berhasil dibatalkan / dihapus.");
    }

    /**
     * Endpoint API JSON untuk pengecekan ketersediaan jadwal secara real-time via AJAX (mendukung multi-day)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'session_id' => 'nullable|integer|exists:booking_sessions,id',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'exclude_event_id' => 'nullable|integer',
        ]);

        $session = null;
        if ($request->filled('session_id')) {
            $session = BookingSession::find($request->session_id);
        }

        $startTime = $session ? substr($session->start_time, 0, 5) : $request->start_time;
        $endTime = $session ? substr($session->end_time, 0, 5) : $request->end_time;
        $startDate = $request->date;
        $endDate = $request->filled('end_date') ? $request->end_date : $request->date;

        if (!$startTime || !$endTime) {
            return response()->json([
                'available' => false,
                'message' => 'Silakan tentukan sesi atau jam peminjaman.',
            ]);
        }

        $query = Event::where('room_id', $request->room_id)
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where('date', '<=', $endDate)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate]);
            });

        if ($session) {
            $query->where(function ($q) use ($session) {
                $q->where('session_id', $session->id)
                  ->orWhere(function ($sub) use ($session) {
                      $sub->where('start_time', '<', $session->end_time)
                          ->where('end_time', '>', $session->start_time);
                  });
            });
        } else {
            $query->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
        }

        if ($request->filled('exclude_event_id')) {
            $query->where('id', '!=', $request->exclude_event_id);
        }

        $conflicting = $query->first();

        if ($conflicting) {
            $labelSesi = $conflicting->session ? " ({$conflicting->session->session_name})" : '';
            $rentangTgl = $conflicting->formatted_date_range;
            return response()->json([
                'available' => false,
                'message' => "Jadwal bentrok dengan kegiatan '{$conflicting->event_name}' ({$rentangTgl}){$labelSesi} (" . substr($conflicting->start_time, 0, 5) . " - " . substr($conflicting->end_time, 0, 5) . " WIB)",
                'conflict' => $conflicting,
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Ruangan dan jadwal rentang waktu tersebut tersedia untuk dipinjam!',
        ]);
    }

    /**
     * Tampilkan formulir pemindahan ruangan (Overriding Waka)
     */
    public function moveRoomForm(Event $event): View
    {
        $rooms = Room::where('is_active', true)
            ->where('id', '!=', $event->room_id)
            ->orderBy('room_name')
            ->get();

        return view('events.move', compact('event', 'rooms'));
    }

    /**
     * Eksekusi hak veto Waka untuk memindahkan ruangan agenda dengan catatan alasan mutlak
     */
    public function moveRoom(MoveRoomRequest $request, Event $event, WhatsAppService $whatsAppService): RedirectResponse
    {
        $ruangLama = $event->room->room_name;
        $ruangBaru = Room::findOrFail($request->new_room_id);

        $event->update([
            'room_id' => $ruangBaru->id,
            'move_reason' => $request->move_reason,
            'approved_by' => Auth::id(),
        ]);

        // REVISI 3: Kirim notifikasi WhatsApp ke peminjam saat perpindahan ruangan berhasil disimpan
        try {
            $event->load(['user', 'room']);
            $whatsAppService->sendRoomMovedNotification($event, $ruangLama, $ruangBaru);
        } catch (\Throwable $e) {
            Log::error("Pengecualian notifikasi WA perpindahan ruangan agenda #{$event->id}: " . $e->getMessage());
        }

        return redirect()->route('events.index')
            ->with('success', "Hak veto Waka berhasil dijalankan: Agenda '{$event->event_name}' dipindahkan dari '{$ruangLama}' ke '{$ruangBaru->room_name}'. Notifikasi alasan pemindahan telah dicatat dan dikirimkan ke peminjam ({$event->user->fullname}).")
            ->with('whatsapp_move_url', $event->whatsappMoveUrl($ruangLama))
            ->with('whatsapp_move_target', $event->user->fullname);
    }
}
