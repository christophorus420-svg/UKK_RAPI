<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRoomRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
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
        $endDate = $request->filled('end_date') ? $request->end_date : null;
        $blocks = $request->resolvedScheduleBlocks();

        $createdCount = 0;
        $eventName = (string) $request->event_name;

        foreach ($blocks as $block) {
            Event::create([
                'room_id' => $request->room_id,
                'session_id' => $block['session_id'],
                'pic_id' => $user->id,
                'event_name' => $eventName,
                'date' => $request->date,
                'end_date' => $endDate,
                'start_time' => $block['start_time'],
                'end_time' => $block['end_time'],
                'status' => $status,
                'approved_by' => $approvedBy,
                'move_reason' => null,
            ]);
            $createdCount++;
        }

        if ($createdCount > 1) {
            $pesanStatus = $status === 'approved'
                ? "Sebanyak {$createdCount} blok sesi untuk agenda '{$eventName}' berhasil dijadwalkan dan langsung disetujui oleh Waka."
                : "Sebanyak {$createdCount} blok sesi pengajuan agenda '{$eventName}' berhasil dikirim dan sedang menunggu persetujuan Waka Sarpras.";

            return redirect()->route('events.index')->with('success', $pesanStatus);
        }

        if ($status === 'approved') {
            return redirect()->route('events.index')
                ->with('success', "Agenda '{$eventName}' berhasil dijadwalkan dan langsung disetujui oleh Waka.");
        }

        return redirect()->route('events.index')
            ->with('success', "Pengajuan agenda '{$eventName}' berhasil dikirim dan sedang menunggu persetujuan Waka Sarpras.");
    }

    public function edit(Event $event): View|RedirectResponse
    {
        if (!$event->canBeEditedBy(Auth::user())) {
            return redirect()->route('events.index')
                ->with('error', 'Anda tidak memiliki hak untuk mengedit agenda ini.');
        }

        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();
        $sessions = BookingSession::where('is_active', true)->orderBy('start_time')->get();

        return view('events.edit', compact('event', 'rooms', 'sessions'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $user = Auth::user();
        $block = $request->resolvedScheduleBlocks()[0];
        $endDate = $request->filled('end_date') ? $request->end_date : null;
        $roomChanged = (int) $event->room_id !== (int) $request->room_id;

        $wasApprovedByNonAdmin = !$user->isAdmin() && $event->status === 'approved';
        $status = $user->isAdmin() ? $event->status : 'pending';
        $approvedBy = $user->isAdmin() ? $event->approved_by : null;

        $event->update([
            'room_id' => $request->room_id,
            'session_id' => $block['session_id'],
            'event_name' => $request->event_name,
            'date' => $request->date,
            'end_date' => $endDate,
            'start_time' => $block['start_time'],
            'end_time' => $block['end_time'],
            'status' => $status,
            'approved_by' => $approvedBy,
            'alasan_penolakan' => $user->isAdmin() ? $event->alasan_penolakan : null,
            'move_reason' => $roomChanged ? null : $event->move_reason,
        ]);

        if ($wasApprovedByNonAdmin) {
            return redirect()->route('events.index')
                ->with('success', "Perubahan agenda '{$event->event_name}' berhasil disimpan dan diajukan kembali untuk persetujuan Waka Sarpras.");
        }

        return redirect()->route('events.index')
            ->with('success', "Peminjaman ruang & agenda '{$event->event_name}' berhasil diperbarui.");
    }

    public function approve(Event $event): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            return back()->with('error', 'Hanya Waka Sarpras / Admin yang berwenang menyetujui agenda.');
        }

        // Validasi tabrakan ulang terhadap agenda lain yang sudah disetujui (mencegah race condition)
        $conflictingEvent = Event::findConflict(
            roomId: (int) $event->room_id,
            startDate: $event->date->toDateString(),
            endDate: $event->end_date?->toDateString(),
            startTime: (string) $event->start_time,
            endTime: (string) $event->end_time,
            sessionId: $event->session_id,
            excludeEventId: $event->id,
            statuses: ['approved']
        );

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
     * Endpoint API JSON untuk pengecekan ketersediaan jadwal secara real-time via AJAX (mendukung multi-day & multi-sesi)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'session_ids' => 'nullable|array',
            'session_ids.*' => 'integer|exists:booking_sessions,id',
            'session_id' => 'nullable|integer|exists:booking_sessions,id',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'exclude_event_id' => 'nullable|integer',
        ]);

        $startDate = (string) $request->date;
        $endDate = $request->filled('end_date') ? (string) $request->end_date : null;
        $excludeEventId = $request->filled('exclude_event_id') ? (int) $request->exclude_event_id : null;

        $blocks = [];
        $sessionIds = array_filter((array) $request->input('session_ids', []), fn ($id) => is_numeric($id));

        if (!empty($sessionIds)) {
            $grouped = BookingSession::groupContiguousIds($sessionIds);
            foreach ($grouped as $block) {
                $blocks[] = [
                    'session_id' => $block['session_id'],
                    'start_time' => $block['start_time'],
                    'end_time' => $block['end_time'],
                ];
            }
        } elseif ($request->filled('session_id')) {
            $session = BookingSession::find($request->session_id);
            if ($session) {
                $blocks[] = [
                    'session_id' => $session->id,
                    'start_time' => substr((string) $session->start_time, 0, 5),
                    'end_time' => substr((string) $session->end_time, 0, 5),
                ];
            }
        } elseif ($request->filled('start_time') && $request->filled('end_time')) {
            $blocks[] = [
                'session_id' => null,
                'start_time' => (string) $request->start_time,
                'end_time' => (string) $request->end_time,
            ];
        }

        if (empty($blocks)) {
            return response()->json([
                'available' => false,
                'message' => 'Silakan pilih minimal satu sesi atau tentukan jam peminjaman.',
            ]);
        }

        if ($excludeEventId && count($blocks) > 1) {
            return response()->json([
                'available' => false,
                'is_gap_warning' => true,
                'message' => 'Untuk mengedit satu agenda, pilih beberapa sesi yang berurutan tanpa melewati sesi di tengahnya.',
            ]);
        }

        foreach ($blocks as $block) {
            $conflicting = Event::findConflict(
                roomId: (int) $request->room_id,
                startDate: $startDate,
                endDate: $endDate,
                startTime: $block['start_time'],
                endTime: $block['end_time'],
                sessionId: $block['session_id'],
                excludeEventId: $excludeEventId
            );

            if ($conflicting) {
                $sessionLabel = $conflicting->session_label;
                $labelSesi = $sessionLabel ? " ({$sessionLabel})" : '';
                $rentangTgl = $conflicting->formatted_date_range;

                return response()->json([
                    'available' => false,
                    'message' => "Jadwal bentrok dengan kegiatan '{$conflicting->event_name}' ({$rentangTgl}){$labelSesi} (" . substr((string) $conflicting->start_time, 0, 5) . ' - ' . substr((string) $conflicting->end_time, 0, 5) . ' WIB)',
                    'conflict' => $conflicting,
                ]);
            }
        }

        $totalSessions = count($sessionIds);
        $pesanTersedia = $totalSessions > 1
            ? "Jadwal {$totalSessions} sesi terpilih tersedia untuk dipinjam!"
            : 'Ruangan dan jadwal rentang waktu tersebut tersedia untuk dipinjam!';

        return response()->json([
            'available' => true,
            'message' => $pesanTersedia,
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
