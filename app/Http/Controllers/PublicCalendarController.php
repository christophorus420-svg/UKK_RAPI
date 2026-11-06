<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCalendarController extends Controller
{
    public function index(Request $request)
    {
        // Validasi dan ambil parameter bulan & tahun
        $now = Carbon::now();
        $selectedMonth = (int) $request->input('month', $now->month);
        $selectedYear = (int) $request->input('year', $now->year);

        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = (int) $now->month;
        }

        if ($selectedYear < 2020 || $selectedYear > 2040) {
            $selectedYear = (int) $now->year;
        }

        $currentMonthDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $prevMonthDate = $currentMonthDate->copy()->subMonth();
        $nextMonthDate = $currentMonthDate->copy()->addMonth();

        // Parameter filter ruangan & pencarian
        $roomId = $request->input('room_id');
        $search = trim($request->input('search', ''));
        $viewMode = $request->input('view', 'grid'); // 'grid' atau 'list'

        // Query agenda resmi (hanya yang sudah disetujui / approved - mendukung rentang tanggal multi-hari)
        $startMonthStr = $currentMonthDate->toDateString();
        $endMonthStr = $currentMonthDate->copy()->endOfMonth()->toDateString();

        $query = Event::with(['room', 'user.role', 'approver'])
            ->where('status', 'approved')
            ->where(function ($q) use ($startMonthStr, $endMonthStr) {
                $q->where('date', '<=', $endMonthStr)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startMonthStr]);
            });

        if (!empty($roomId)) {
            $query->where('room_id', $roomId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('fullname', 'like', "%{$search}%");
                  })
                  ->orWhereHas('room', function ($rq) use ($search) {
                      $rq->where('room_name', 'like', "%{$search}%")
                         ->orWhere('location', 'like', "%{$search}%");
                  });
            });
        }

        $events = $query->orderBy('date')->orderBy('start_time')->get();

        // Jika request menginginkan JSON (untuk integrasi API atau Flutter)
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'total_events' => $events->count(),
                'data' => $events,
            ]);
        }

        // Kalkulasi matriks grid kalender (Senin - Minggu)
        // dayOfWeekIso: 1 (Senin) s/d 7 (Minggu)
        $firstDayOfWeekIso = $currentMonthDate->dayOfWeekIso;
        $leadingBlankDays = $firstDayOfWeekIso - 1; // Jumlah kotak kosong di awal bulan
        $daysInMonth = $currentMonthDate->daysInMonth;
        $totalCellsUsed = $leadingBlankDays + $daysInMonth;
        $trailingBlankDays = ($totalCellsUsed % 7 === 0) ? 0 : 7 - ($totalCellsUsed % 7);

        // Kelompokkan agenda berdasarkan tanggal (YYYY-MM-DD), mendistribusikan event multi-hari ke setiap harinya
        $eventsByDate = collect();
        foreach ($events as $item) {
            $startDate = Carbon::parse($item->date);
            $endDate = $item->end_date ? Carbon::parse($item->end_date) : $startDate->copy();

            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $dateKey = $cursor->format('Y-m-d');
                if (!$eventsByDate->has($dateKey)) {
                    $eventsByDate->put($dateKey, collect());
                }
                $eventsByDate->get($dateKey)->push($item);
                $cursor->addDay();
            }
        }

        // Daftar hari penting nasional / sekolah
        $importantDates = [
            '01-01' => 'Tahun Baru Masehi',
            '05-01' => 'Hari Buruh Internasional',
            '05-02' => 'Hari Pendidikan Nasional (Hardiknas)',
            '05-20' => 'Hari Kebangkitan Nasional',
            '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan RI',
            '10-01' => 'Hari Kesaktian Pancasila',
            '10-28' => 'Hari Sumpah Pemuda',
            '11-10' => 'Hari Pahlawan Nasional',
            '11-25' => 'Hari Guru Nasional',
            '12-25' => 'Hari Raya Natal',
        ];

        // Daftar ruangan aktif untuk filter
        $rooms = Room::where('is_active', true)->orderBy('room_name')->get();

        // Statistik ringkas bulan ini
        $stats = [
            'total_approved_events' => $events->count(),
            'total_rooms_used' => $events->pluck('room_id')->unique()->count(),
            'active_room_count' => $rooms->count(),
        ];

        return view('public.calendar', [
            'events' => $events,
            'eventsByDate' => $eventsByDate,
            'importantDates' => $importantDates,
            'rooms' => $rooms,
            'currentMonthDate' => $currentMonthDate,
            'prevMonthDate' => $prevMonthDate,
            'nextMonthDate' => $nextMonthDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedRoomId' => $roomId,
            'search' => $search,
            'viewMode' => $viewMode,
            'leadingBlankDays' => $leadingBlankDays,
            'daysInMonth' => $daysInMonth,
            'trailingBlankDays' => $trailingBlankDays,
            'todayString' => Carbon::now()->format('Y-m-d'),
            'stats' => $stats,
        ]);
    }
}
