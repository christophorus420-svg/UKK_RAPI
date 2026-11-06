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
}
