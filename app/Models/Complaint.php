<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'event_id',
        'reporter_id',
        'description',
        'photo_path',
        'status_repair',
        'repair_notes',
        'repaired_at',
        'handler_id',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'repaired_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Complaint $complaint) {
            if ($complaint->status_repair === 'Sudah Diperbaiki') {
                if (!$complaint->repaired_at) {
                    $complaint->repaired_at = now();
                }
            } else {
                // Jika status diubah kembali ke Belum Diperbaiki / Menunggu Spare Part, reset repaired_at
                if ($complaint->isDirty('status_repair')) {
                    $complaint->repaired_at = null;
                }
            }
        });
    }

    /**
     * Otomatis menghapus pengaduan berstatus 'Sudah Diperbaiki' yang telah melewati 24 jam
     */
    public static function purgeResolvedOverdue(): int
    {
        $cutoff = now()->subHours(24);
        $expiredComplaints = static::where('status_repair', 'Sudah Diperbaiki')
            ->where(function ($query) use ($cutoff) {
                $query->where('repaired_at', '<=', $cutoff)
                    ->orWhere(function ($sub) use ($cutoff) {
                        $sub->whereNull('repaired_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->get();

        $count = 0;
        foreach ($expiredComplaints as $complaint) {
            if ($complaint->photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($complaint->photo_path);
            }
            $complaint->delete();
            $count++;
        }

        return $count;
    }

    /**
     * Menghitung perkiraan sisa waktu sebelum pengaduan dihapus otomatis (24 jam setelah selesai diperbaiki)
     */
    public function getAutoDeleteRemainingAttribute(): ?string
    {
        if ($this->status_repair !== 'Sudah Diperbaiki') {
            return null;
        }

        $repairedTime = $this->repaired_at ?? $this->updated_at;
        if (!$repairedTime) {
            return null;
        }

        $deleteTime = \Carbon\Carbon::parse($repairedTime)->addHours(24);
        $diffMinutes = (int) now()->diffInMinutes($deleteTime, false);

        if ($diffMinutes <= 0) {
            return 'Segera dihapus';
        }

        $hours = intdiv($diffMinutes, 60);
        $minutes = $diffMinutes % 60;

        if ($hours > 0) {
            return "{$hours} jam {$minutes} mnt";
        }

        return "{$minutes} menit";
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function handler(): BelongsTo
    {
        return $this->handler_id
            ? $this->belongsTo(User::class, 'handler_id')
            : $this->belongsTo(User::class, 'room.pic_id');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    /**
     * Memeriksa apakah pengaduan berstatus Belum Diperbaiki dan melewati batas waktu (>= 2 hari)
     */
    public function isOverdue(): bool
    {
        return $this->status_repair === 'Belum Diperbaiki'
            && \Carbon\Carbon::parse($this->reported_at)->diffInDays(now()) >= 2;
    }

    /**
     * Menghasilkan URL direct WhatsApp API lengkap dengan format pesan eskalasi resmi
     */
    public function getWhatsappEscalationUrlAttribute(): string
    {
        $picTarget = $this->handler ?? $this->room?->pic;
        $nomorWa = $picTarget?->whatsapp_number ?? '6281234567890';
        $namaPic = $picTarget?->fullname ?? 'Petugas PIC Ruangan';
        $namaRuang = $this->room?->room_name ?? 'Fasilitas Sekolah';
        $tanggalLapor = \Carbon\Carbon::parse($this->reported_at)->translatedFormat('d F Y');

        $pesan = "Yth. Bpk/Ibu {$namaPic},\n\n"
            . "*PERINGATAN ESKALASI SARPRAS (RAPI-SEKOLAH)*\n"
            . "Terdapat fasilitas yang dilaporkan rusak dan masih berstatus *Belum Diperbaiki* melewati batas waktu:\n"
            . "• Ruangan: {$namaRuang}\n"
            . "• Kendala Fisik: \"{$this->description}\"\n"
            . "• Dilaporkan Pada: {$tanggalLapor}\n"
            . "• Status Saat Ini: {$this->status_repair}\n\n"
            . "Mohon segera melakukan tindakan pengecekan fisik dan memperbarui status penanganan menjadi 'Menunggu Spare Part' atau 'Sudah Diperbaiki' di sistem RAPI-Sekolah.\n\n"
            . "Terima kasih.\n"
            . "- Waka Sarana & Prasarana Sekolah";

        return "https://wa.me/{$nomorWa}?text=" . rawurlencode($pesan);
    }
}
