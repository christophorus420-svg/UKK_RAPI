<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Sarpras - {{ $periodLabel }}</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm;
            size: a4 landscape;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        .header-kop {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header-kop h2 {
            font-size: 15px;
            font-weight: bold;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .header-kop h3 {
            font-size: 12px;
            font-weight: 600;
            margin: 0 0 3px 0;
            color: #2563eb;
        }

        .header-kop p {
            font-size: 8.5px;
            color: #64748b;
            margin: 0;
        }

        .report-title-box {
            text-align: center;
            margin-bottom: 14px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0 0 3px 0;
        }

        .report-subtitle {
            font-size: 10px;
            color: #475569;
            margin: 0;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 12px;
            font-size: 9.5px;
        }

        .meta-table td {
            padding: 2px 4px;
        }

        .stats-container {
            width: 100%;
            margin-bottom: 14px;
        }

        .stats-box {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 6px 10px;
            display: inline-block;
            margin-right: 8px;
            font-size: 9px;
        }

        .stats-box strong {
            font-size: 11px;
            color: #0f172a;
        }

        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            margin: 14px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
            text-transform: uppercase;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }

        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #fafaf9;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
            border: 0.5px solid #bbf7d0;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 0.5px solid #fde68a;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 0.5px solid #fecaca;
        }

        .badge-info {
            background-color: #e0f2fe;
            color: #075985;
            border: 0.5px solid #bae6fd;
        }

        .signature-section {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signature-box {
            width: 250px;
            float: right;
            text-align: center;
            font-size: 9.5px;
        }

        .signature-space {
            height: 50px;
        }
    </style>
</head>
<body>

    <!-- Header Kop Surat -->
    <table style="width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 12px;">
        <tr>
            <td style="width: 55px; vertical-align: middle; text-align: left;">
                <img src="{{ public_path('images/logo.png') }}" style="width: 48px; height: 48px; object-fit: contain;">
            </td>
            <td style="text-align: center; vertical-align: middle; padding-right: 55px;">
                <h2 style="font-size: 11px; font-weight: bold; margin: 0 0 2px 0; color: #0f172a; text-transform: uppercase;">PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA</h2>
                <h3 style="font-size: 13px; font-weight: bold; margin: 0 0 2px 0; color: #1e3a8a; text-transform: uppercase;">SMK NEGERI 1 BANTUL</h3>
                <p style="font-size: 8px; color: #475569; margin: 0;">Sistem Informasi Agenda & Sarana Prasarana (RAPI-Sekolah) | Sabdodadi, Bantul, DIY</p>
            </td>
        </tr>
    </table>

    <!-- Judul Laporan -->
    <div class="report-title-box">
        <div class="report-title">REKAPITULASI DATA PEMINJAMAN RUANGAN & PENGADUAN FASILITAS</div>
        <div class="report-subtitle">Periode: <strong>{{ $periodLabel }}</strong> (Mode: {{ strtoupper($period) }})</div>
    </div>

    <!-- Metadata Laporan -->
    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Filter Kategori:</strong></td>
            <td width="35%">{{ strtoupper($type === 'all' ? 'Semua (Peminjaman & Pengaduan)' : ($type === 'events' ? 'Peminjaman Ruang Saja' : 'Pengaduan Kerusakan Saja')) }}</td>
            <td width="18%"><strong>Filter Ruangan:</strong></td>
            <td width="32%">{{ $selectedRoom ? $selectedRoom->room_name : 'Semua Ruangan' }}</td>
        </tr>
        <tr>
            <td><strong>Waktu Dokumen:</strong></td>
            <td>{{ $generatedAt }} WIB</td>
            <td><strong>Total Data:</strong></td>
            <td>{{ $events->count() }} Peminjaman, {{ $complaints->count() }} Pengaduan</td>
        </tr>
    </table>

    <!-- Ringkasan Statistik -->
    <div class="stats-container">
        <div class="stats-box">
            Total Peminjaman: <strong>{{ $stats['total_events'] }}</strong>
            (Disetujui: {{ $stats['approved_events'] }}, Pending: {{ $stats['pending_events'] }}, Ditolak: {{ $stats['rejected_events'] }})
        </div>
        <div class="stats-box">
            Total Pengaduan: <strong>{{ $stats['total_complaints'] }}</strong>
            (Selesai: {{ $stats['resolved_complaints'] }}, Part: {{ $stats['progress_complaints'] }}, Belum: {{ $stats['pending_complaints'] }})
        </div>
    </div>

    <!-- 1. Tabel Peminjaman Ruangan -->
    @if ($type === 'all' || $type === 'events')
        <div class="section-header">I. Data Peminjaman & Agenda Penggunaan Ruangan</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="10%">Tanggal</th>
                    <th width="13%">Ruangan</th>
                    <th width="13%">Sesi / Jam</th>
                    <th width="20%">Nama Agenda</th>
                    <th width="13%">Peminjam / PIC</th>
                    <th width="10%">Status</th>
                    <th width="17%">Catatan / Alasan Tolak</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $index => $event)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($event->date)->translatedFormat('d/m/Y') }}</td>
                        <td><strong>{{ $event->room ? $event->room->room_name : '-' }}</strong></td>
                        <td>
                            @if ($event->session_label)
                                <strong>{{ $event->session_label }}</strong><br>
                                <span style="color: #64748b;">{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</span>
                            @else
                                {{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}
                            @endif
                        </td>
                        <td><strong>{{ $event->event_name }}</strong></td>
                        <td>{{ $event->user ? $event->user->fullname : '-' }}</td>
                        <td>
                            @if ($event->status === 'approved')
                                <span class="badge badge-success">Disetujui</span>
                            @elseif ($event->status === 'rejected')
                                <span class="badge badge-danger">Ditolak</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                        <td>
                            @if ($event->alasan_penolakan)
                                <span style="color: #991b1b;"><strong>Ditolak:</strong> {{ $event->alasan_penolakan }}</span>
                            @elseif ($event->move_reason)
                                <span style="color: #92400e;"><strong>Pindah:</strong> {{ $event->move_reason }}</span>
                            @else
                                <span style="color: #94a3b8;">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 10px;">
                            Tidak ada data peminjaman ruangan yang tercatat pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- 2. Tabel Pengaduan Sarpras -->
    @if ($type === 'all' || $type === 'complaints')
        <div class="section-header">II. Data Pengaduan Kerusakan Sarana & Prasarana</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="12%">Waktu Lapor</th>
                    <th width="13%">Ruangan</th>
                    <th width="15%">Terkait Agenda</th>
                    <th width="13%">Pelapor</th>
                    <th width="23%">Deskripsi Kerusakan</th>
                    <th width="10%">Status</th>
                    <th width="10%">Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($complaints as $index => $c)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>
                            {{ $c->reported_at ? \Carbon\Carbon::parse($c->reported_at)->translatedFormat('d/m/Y H:i') : ($c->created_at ? \Carbon\Carbon::parse($c->created_at)->translatedFormat('d/m/Y H:i') : '-') }}
                        </td>
                        <td><strong>{{ $c->room ? $c->room->room_name : '-' }}</strong></td>
                        <td>
                            @if ($c->event)
                                <strong>{{ $c->event->event_name }}</strong><br>
                                <span style="color: #64748b;">{{ \Carbon\Carbon::parse($c->event->date)->translatedFormat('d/m/Y') }}</span>
                            @else
                                <span style="color: #94a3b8;">Reguler</span>
                            @endif
                        </td>
                        <td>{{ $c->reporter ? $c->reporter->fullname : '-' }}</td>
                        <td>{{ $c->description }}</td>
                        <td>
                            @if ($c->status_repair === 'Sudah Diperbaiki')
                                <span class="badge badge-success">Selesai</span>
                            @elseif ($c->status_repair === 'Menunggu Spare Part')
                                <span class="badge badge-warning">Menunggu Part</span>
                            @else
                                <span class="badge badge-danger">Belum Selesai</span>
                            @endif
                        </td>
                        <td>
                            {{ $c->handler ? $c->handler->fullname : ($c->room && $c->room->pic ? $c->room->pic->fullname : '-') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #64748b; padding: 10px;">
                            Tidak ada pengaduan kerusakan sarpras yang tercatat pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Tanda Tangan Pengesahan -->
    <div class="signature-section">
        <div class="signature-box">
            <div>Mengetahui,</div>
            <div style="font-weight: bold;">Kepala Subbag Sarana & Prasarana</div>
            <div class="signature-space"></div>
            <div style="font-weight: bold; text-decoration: underline;">( Administrator Sarpras )</div>
            <div style="color: #64748b;">NIP. 19850715 201001 1 008</div>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>
</html>
