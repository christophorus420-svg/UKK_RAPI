<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'session_id',
        'pic_id',
        'event_name',
        'date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'move_reason',
        'alasan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(BookingSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function complaints(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Complaint::class, 'event_id');
    }

    public function isMultiDay(): bool
    {
        return $this->end_date !== null && $this->end_date->gt($this->date);
    }

    public function getEndDateResolvedAttribute(): Carbon
    {
        return $this->end_date ? Carbon::parse($this->end_date) : Carbon::parse($this->date);
    }

    public function getFormattedDateRangeAttribute(): string
    {
        $startDate = Carbon::parse($this->date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : $startDate;

        if ($startDate->isSameDay($endDate)) {
            return $startDate->translatedFormat('d M Y');
        }

        return $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y');
    }

    /**
     * Menghitung status pelaksanaan dinamis berdasarkan waktu saat ini:
     * - Jika status pending: 'Pending'
     * - Jika status rejected: 'Ditolak'
     * - Jika status approved:
     *   * Sebelum jam mulai: 'Belum Terlaksana'
     *   * Dalam rentang jam mulai s/d selesai: 'Sedang Berlangsung'
     *   * Setelah jam selesai: 'Sudah Terlaksana'
     */
    public function getExecutionStatusAttribute(): string
    {
        if ($this->status === 'pending') {
            return 'Pending';
        }

        if ($this->status === 'rejected') {
            return 'Ditolak';
        }

        $now = Carbon::now();
        $startDateStr = Carbon::parse($this->date)->toDateString();
        $endDateStr = ($this->end_date ? Carbon::parse($this->end_date) : Carbon::parse($this->date))->toDateString();

        $startDateTime = Carbon::parse("{$startDateStr} {$this->start_time}");
        $endDateTime = Carbon::parse("{$endDateStr} {$this->end_time}");

        if ($now->lt($startDateTime)) {
            return 'Belum Terlaksana';
        }

        if ($now->betweenIncluded($startDateTime, $endDateTime)) {
            return 'Sedang Berlangsung';
        }

        return 'Sudah Terlaksana';
    }

    /**
     * Mengecek apakah agenda sedang berjalan pada detik/jam saat ini
     */
    public function isOngoingNow(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $now = Carbon::now();
        $startDateStr = Carbon::parse($this->date)->toDateString();
        $endDateStr = ($this->end_date ? Carbon::parse($this->end_date) : Carbon::parse($this->date))->toDateString();

        $startDateTime = Carbon::parse("{$startDateStr} {$this->start_time}");
        $endDateTime = Carbon::parse("{$endDateStr} {$this->end_time}");

        return $now->betweenIncluded($startDateTime, $endDateTime);
    }

    /**
     * Menyusun teks pesan WhatsApp peringatan atau pemberitahuan perpindahan ruangan untuk peminjam
     */
    public function buildWhatsappMoveMessage(?string $oldRoomName = null): string
    {
        $namaGuru = $this->user?->fullname ?? 'Bpk/Ibu Guru Peminjam';
        $namaRuang = $this->room?->room_name ?? 'Ruangan Sekolah';
        $lokasiRuang = $this->room?->location;
        $ruangLengkap = $lokasiRuang ? "{$namaRuang} ({$lokasiRuang})" : $namaRuang;

        $tanggalPeminjaman = Carbon::parse($this->date)->translatedFormat('l, d F Y');
        if ($this->isMultiDay()) {
            $tanggalPeminjaman .= ' s/d ' . Carbon::parse($this->end_date)->translatedFormat('l, d F Y');
        }

        $jamMulai = substr((string) $this->start_time, 0, 5);
        $jamSelesai = substr((string) $this->end_time, 0, 5);

        if (!empty($this->move_reason) || !empty($oldRoomName)) {
            $barisRuang = $oldRoomName
                ? "• Perpindahan Ruangan: dari {$oldRoomName} ke {$ruangLengkap}"
                : "• Ruangan Pengganti / Baru: {$ruangLengkap}";

            $alasan = !empty($this->move_reason)
                ? $this->move_reason
                : 'Penyesuaian kebutuhan mendesak sekolah';

            return "Yth. Bpk/Ibu {$namaGuru},\n\n"
                . "*PERINGATAN PERPINDAHAN RUANGAN (RAPI-SEKOLAH)*\n"
                . "Peminjaman ruangan Anda mengalami pemindahan ruangan oleh Waka Sarpras:\n"
                . "• Agenda/Kegiatan: \"{$this->event_name}\"\n"
                . "• Tanggal & Waktu: {$tanggalPeminjaman} (pukul {$jamMulai} - {$jamSelesai} WIB)\n"
                . "{$barisRuang}\n"
                . "• Alasan Pemindahan: \"{$alasan}\"\n\n"
                . "Mohon menyesuaikan lokasi kegiatan ke ruangan pengganti tersebut dan memeriksa detail jadwal pada sistem RAPI-Sekolah.\n\n"
                . "Terima kasih.\n"
                . "- Waka Sarana & Prasarana Sekolah";
        }

        return "Yth. Bpk/Ibu {$namaGuru},\n\n"
            . "*PERINGATAN PERPINDAHAN RUANGAN (RAPI-SEKOLAH)*\n"
            . "Terdapat pemberitahuan rencana pemindahan/penyesuaian ruangan untuk peminjaman Anda oleh Waka Sarpras:\n"
            . "• Agenda/Kegiatan: \"{$this->event_name}\"\n"
            . "• Tanggal & Waktu: {$tanggalPeminjaman} (pukul {$jamMulai} - {$jamSelesai} WIB)\n"
            . "• Ruangan Saat Ini: {$ruangLengkap}\n\n"
            . "Mohon kesediaannya untuk berkoordinasi dengan Waka Sarpras dan memantau pembaruan ruangan pengganti di sistem RAPI-Sekolah.\n\n"
            . "Terima kasih.\n"
            . "- Waka Sarana & Prasarana Sekolah";
    }

    /**
     * Menghasilkan URL direct WhatsApp API (wa.me) untuk peringatan perpindahan ruangan ke peminjam
     */
    public function whatsappMoveUrl(?string $oldRoomName = null): string
    {
        $nomorWa = $this->user?->whatsapp_number ?? '6281234567890';

        return "https://wa.me/{$nomorWa}?text=" . rawurlencode($this->buildWhatsappMoveMessage($oldRoomName));
    }

    public function getWhatsappMoveWarningUrlAttribute(): string
    {
        return $this->whatsappMoveUrl();
    }

    /**
     * Mengecek apakah pengguna berhak mengedit peminjaman ruangan & agenda ini.
     * Admin selalu diizinkan; pemohon (guru/pic) diizinkan untuk agendanya sendiri
     * selama agenda approved belum melewati waktu selesai (atau masih pending/rejected).
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ((int) $this->pic_id !== (int) $user->id) {
            return false;
        }

        if ($this->status === 'approved') {
            $endDateStr = $this->end_date_resolved->toDateString();
            $endDateTime = Carbon::parse("{$endDateStr} {$this->end_time}");

            return !$endDateTime->isPast();
        }

        return true;
    }

    /**
     * Mengambil daftar sesi pelajaran yang tercakup dalam rentang waktu event ini (mendukung multi-sesi).
     *
     * @return \Illuminate\Support\Collection<int, BookingSession>
     */
    public function getMatchedSessionsAttribute(): \Illuminate\Support\Collection
    {
        $evStart = substr((string) $this->start_time, 0, 5);
        $evEnd = substr((string) $this->end_time, 0, 5);

        $matched = BookingSession::where('is_active', true)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get()
            ->filter(function (BookingSession $session) use ($evStart, $evEnd) {
                $sStart = substr((string) $session->start_time, 0, 5);
                $sEnd = substr((string) $session->end_time, 0, 5);

                return $sStart < $evEnd && $sEnd > $evStart;
            })
            ->values();

        if ($matched->isEmpty() && $this->session) {
            return collect([$this->session]);
        }

        return $matched;
    }

    /**
     * Daftar ID sesi yang sesuai dengan rentang waktu agenda (untuk pre-fill form edit multi-sesi).
     *
     * @return array<int, int>
     */
    public function getMatchedSessionIdsAttribute(): array
    {
        $ids = $this->matched_sessions->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($ids) && $this->session_id) {
            return [(int) $this->session_id];
        }

        return $ids;
    }

    /**
     * Label tampilan sesi ringkas (1 sesi atau rentang multi-sesi seperti "Sesi 1 s.d. Sesi 3").
     */
    public function getSessionLabelAttribute(): ?string
    {
        $matched = $this->matched_sessions;

        if ($matched->isEmpty()) {
            return $this->session?->session_name;
        }

        if ($matched->count() === 1) {
            return $matched->first()->session_name;
        }

        return "{$matched->first()->session_name} s.d. {$matched->last()->session_name}";
    }

    /**
     * Pencarian jadwal bentrok tunggal yang digunakan ulang oleh validasi store, update, approve, dan AJAX cek ketersediaan.
     *
     * @param  array<int, string>  $statuses
     */
    public static function findConflict(
        int $roomId,
        string $startDate,
        ?string $endDate,
        string $startTime,
        string $endTime,
        ?int $sessionId = null,
        ?int $excludeEventId = null,
        array $statuses = ['approved', 'pending']
    ): ?self {
        $effectiveEndDate = $endDate ?: $startDate;
        $normalizedStart = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
        $normalizedEnd = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;

        $query = self::with('session')
            ->where('room_id', $roomId)
            ->whereIn('status', $statuses)
            ->where(function ($q) use ($startDate, $effectiveEndDate) {
                $q->where('date', '<=', $effectiveEndDate)
                  ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate]);
            })
            ->where(function ($q) use ($normalizedStart, $normalizedEnd, $sessionId) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId)
                      ->orWhere(function ($sub) use ($normalizedStart, $normalizedEnd) {
                          $sub->where('start_time', '<', $normalizedEnd)
                              ->where('end_time', '>', $normalizedStart);
                      });
                } else {
                    $q->where('start_time', '<', $normalizedEnd)
                        ->where('end_time', '>', $normalizedStart);
                }
            });

        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }

        return $query->first();
    }
}
