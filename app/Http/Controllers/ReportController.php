<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Tampilkan halaman filter dan rekap laporan peminjaman & pengaduan.
     */
    public function index(Request $request)
    {
        $params = $this->parseReportParams($request);
        $data = $this->getReportData($params);

        $rooms = Room::orderBy('room_name')->get();

        return view('reports.index', array_merge($params, $data, [
            'rooms' => $rooms,
        ]));
    }

    /**
     * Unduh laporan dalam format PDF.
     */
    public function exportPdf(Request $request)
    {
        $params = $this->parseReportParams($request);
        $data = $this->getReportData($params);

        $selectedRoom = $params['room_id'] ? Room::find($params['room_id']) : null;

        $pdf = Pdf::loadView('reports.pdf', array_merge($params, $data, [
            'selectedRoom' => $selectedRoom,
            'generatedAt' => Carbon::now()->translatedFormat('d F Y H:i'),
        ]))->setPaper('a4', 'landscape');

        $filename = 'Laporan-Sarpras-' . $params['period'] . '-' . str_replace([' ', '/', ':'], '-', $params['periodLabel']) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Unduh laporan dalam format Excel / CSV (disertai UTF-8 BOM untuk kompatibilitas Excel).
     */
    public function exportCsv(Request $request)
    {
        $params = $this->parseReportParams($request);
        $data = $this->getReportData($params);

        $filename = 'Laporan-Sarpras-' . $params['period'] . '-' . str_replace([' ', '/', ':'], '-', $params['periodLabel']) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($params, $data) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar Microsoft Excel membuka aksen dan karakter dengan benar
            fputs($handle, "\xEF\xBB\xBF");

            // Header Laporan
            fputcsv($handle, ['REKAPITULASI LAPORAN SARPRAS (RAPI-SEKOLAH)']);
            fputcsv($handle, ['Periode:', $params['periodLabel']]);
            fputcsv($handle, ['Kategori:', strtoupper($params['type'])]);
            fputcsv($handle, ['Waktu Unduh:', Carbon::now()->translatedFormat('d F Y H:i:s')]);
            fputcsv($handle, []);

            // 1. Bagian Peminjaman Ruangan
            if ($params['type'] === 'all' || $params['type'] === 'events') {
                fputcsv($handle, ['=== DATA PEMINJAMAN RUANGAN ===']);
                fputcsv($handle, [
                    'No',
                    'Tanggal',
                    'Ruangan',
                    'Sesi / Jam',
                    'Nama Agenda',
                    'Peminjam / PIC',
                    'Status',
                    'Penyetuju / Penolak',
                    'Alasan Penolakan / Catatan',
                ]);

                $no = 1;
                foreach ($data['events'] as $event) {
                    $sessionLabel = $event->session
                        ? $event->session->session_name . ' (' . substr($event->session->start_time, 0, 5) . ' - ' . substr($event->session->end_time, 0, 5) . ')'
                        : substr($event->start_time, 0, 5) . ' - ' . substr($event->end_time, 0, 5);

                    $statusLabel = match ($event->status) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu Persetujuan',
                    };

                    fputcsv($handle, [
                        $no++,
                        Carbon::parse($event->date)->translatedFormat('d/m/Y'),
                        $event->room ? $event->room->room_name : '-',
                        $sessionLabel,
                        $event->event_name,
                        $event->user ? $event->user->fullname : '-',
                        $statusLabel,
                        $event->approver ? $event->approver->fullname : '-',
                        $event->alasan_penolakan ?: ($event->move_reason ?: '-'),
                    ]);
                }

                fputcsv($handle, []);
            }

            // 2. Bagian Pengaduan Kerusakan Sarpras
            if ($params['type'] === 'all' || $params['type'] === 'complaints') {
                fputcsv($handle, ['=== DATA PENGADUAN KERUSAKAN SARPRAS ===']);
                fputcsv($handle, [
                    'No',
                    'Tanggal Pengaduan',
                    'Ruangan',
                    'Terkait Peminjaman',
                    'Pelapor',
                    'Deskripsi Kerusakan',
                    'Status Perbaikan',
                    'Petugas Penanggung Jawab',
                ]);

                $no = 1;
                foreach ($data['complaints'] as $complaint) {
                    $reportedDate = $complaint->reported_at
                        ? Carbon::parse($complaint->reported_at)->translatedFormat('d/m/Y H:i')
                        : ($complaint->created_at ? Carbon::parse($complaint->created_at)->translatedFormat('d/m/Y H:i') : '-');

                    $eventRef = $complaint->event
                        ? $complaint->event->event_name . ' (' . Carbon::parse($complaint->event->date)->translatedFormat('d/m/Y') . ')'
                        : '-';

                    fputcsv($handle, [
                        $no++,
                        $reportedDate,
                        $complaint->room ? $complaint->room->room_name : '-',
                        $eventRef,
                        $complaint->reporter ? $complaint->reporter->fullname : '-',
                        $complaint->description,
                        $complaint->status_repair,
                        $complaint->handler ? $complaint->handler->fullname : ($complaint->room && $complaint->room->pic ? $complaint->room->pic->fullname : '-'),
                    ]);
                }
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Parsing dan sanitasi parameter filter laporan.
     */
    private function parseReportParams(Request $request): array
    {
        $period = $request->input('period', 'monthly'); // 'daily' atau 'monthly'
        if (!in_array($period, ['daily', 'monthly'])) {
            $period = 'monthly';
        }

        $type = $request->input('type', 'all'); // 'all', 'events', 'complaints'
        if (!in_array($type, ['all', 'events', 'complaints'])) {
            $type = 'all';
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $month = (int) $request->input('month', Carbon::now()->format('n'));
        $year = (int) $request->input('year', Carbon::now()->format('Y'));

        if ($month < 1 || $month > 12) {
            $month = (int) Carbon::now()->format('n');
        }

        $roomId = $request->input('room_id');
        $status = $request->input('status');

        $indonesianMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if ($period === 'daily') {
            $periodLabel = Carbon::parse($date)->translatedFormat('d F Y');
        } else {
            $periodLabel = ($indonesianMonths[$month] ?? $month) . ' ' . $year;
        }

        return [
            'period' => $period,
            'type' => $type,
            'date' => $date,
            'month' => $month,
            'year' => $year,
            'room_id' => $roomId,
            'status' => $status,
            'periodLabel' => $periodLabel,
            'indonesianMonths' => $indonesianMonths,
        ];
    }

    /**
     * Mengambil data peminjaman dan pengaduan sesuai filter.
     */
    private function getReportData(array $params): array
    {
        $events = collect();
        $complaints = collect();

        // 1. Ambil data Peminjaman jika type = 'all' atau 'events'
        if ($params['type'] === 'all' || $params['type'] === 'events') {
            $eventsQuery = Event::with(['room', 'session', 'user', 'approver']);

            if ($params['period'] === 'daily') {
                $eventsQuery->whereDate('date', $params['date']);
            } else {
                $eventsQuery->whereYear('date', $params['year'])
                    ->whereMonth('date', $params['month']);
            }

            if (!empty($params['room_id'])) {
                $eventsQuery->where('room_id', $params['room_id']);
            }

            if (!empty($params['status']) && in_array($params['status'], ['pending', 'approved', 'rejected'])) {
                $eventsQuery->where('status', $params['status']);
            }

            $events = $eventsQuery->orderBy('date', 'desc')
                ->orderBy('start_time', 'asc')
                ->get();
        }

        // 2. Ambil data Pengaduan jika type = 'all' atau 'complaints'
        if ($params['type'] === 'all' || $params['type'] === 'complaints') {
            $complaintsQuery = Complaint::with(['room', 'event', 'reporter', 'handler']);

            if ($params['period'] === 'daily') {
                $complaintsQuery->where(function ($q) use ($params) {
                    $q->whereDate('reported_at', $params['date'])
                      ->orWhere(function ($sub) use ($params) {
                          $sub->whereNull('reported_at')->whereDate('created_at', $params['date']);
                      });
                });
            } else {
                $complaintsQuery->where(function ($q) use ($params) {
                    $q->whereYear('reported_at', $params['year'])
                      ->whereMonth('reported_at', $params['month'])
                      ->orWhere(function ($sub) use ($params) {
                          $sub->whereNull('reported_at')
                              ->whereYear('created_at', $params['year'])
                              ->whereMonth('created_at', $params['month']);
                      });
                });
            }

            if (!empty($params['room_id'])) {
                $complaintsQuery->where('room_id', $params['room_id']);
            }

            if (!empty($params['status']) && in_array($params['status'], ['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'])) {
                $complaintsQuery->where('status_repair', $params['status']);
            }

            $complaints = $complaintsQuery->orderBy('reported_at', 'desc')->get();
        }

        // Hitung statistik
        $stats = [
            'total_events' => $events->count(),
            'approved_events' => $events->where('status', 'approved')->count(),
            'pending_events' => $events->where('status', 'pending')->count(),
            'rejected_events' => $events->where('status', 'rejected')->count(),
            'total_complaints' => $complaints->count(),
            'pending_complaints' => $complaints->where('status_repair', 'Belum Diperbaiki')->count(),
            'progress_complaints' => $complaints->where('status_repair', 'Menunggu Spare Part')->count(),
            'resolved_complaints' => $complaints->where('status_repair', 'Sudah Diperbaiki')->count(),
        ];

        return [
            'events' => $events,
            'complaints' => $complaints,
            'stats' => $stats,
        ];
    }
}
