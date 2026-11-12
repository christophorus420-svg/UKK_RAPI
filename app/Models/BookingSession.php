<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_name',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'session_id');
    }

    public function getFormattedTimeAttribute(): string
    {
        return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5) . ' WIB';
    }

    public function getFullLabelAttribute(): string
    {
        return "{$this->session_name} ({$this->formatted_time})";
    }

    /**
     * Mengelompokkan daftar ID sesi terpilih menjadi blok-blok waktu berurutan (tanpa jeda sesi di tengah).
     * Contoh: [1, 2, 3] => 1 blok (Sesi 1 s.d. Sesi 3); [1, 3] => 2 blok terpisah agar Sesi 2 tetap kosong.
     *
     * @param  array<int|string>  $sessionIds
     * @return array<int, array{sessions: \Illuminate\Support\Collection<int, self>, session_id: int|null, start_time: string, end_time: string}>
     */
    public static function groupContiguousIds(array $sessionIds, bool $onlyActive = true): array
    {
        $ids = collect($sessionIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $query = self::query()->orderBy('start_time')->orderBy('id');
        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $orderedSessions = $query->get()->values();
        $indexById = [];
        foreach ($orderedSessions as $index => $session) {
            $indexById[$session->id] = $index;
        }

        $selected = $orderedSessions
            ->filter(fn (self $session) => $ids->contains($session->id))
            ->values();

        if ($selected->isEmpty()) {
            return [];
        }

        $blocks = [];
        $currentGroup = collect([$selected->first()]);

        for ($i = 1; $i < $selected->count(); $i++) {
            /** @var self $prev */
            $prev = $currentGroup->last();
            /** @var self $curr */
            $curr = $selected->get($i);

            $prevIndex = $indexById[$prev->id] ?? -10;
            $currIndex = $indexById[$curr->id] ?? -1;
            $prevEnd = substr((string) $prev->end_time, 0, 5);
            $currStart = substr((string) $curr->start_time, 0, 5);

            // Berurutan jika tepat berada di posisi selanjutnya pada daftar sesi, atau waktunya menyambung/beririsan
            if ($currIndex === $prevIndex + 1 || $currStart <= $prevEnd) {
                $currentGroup->push($curr);
            } else {
                $blocks[] = self::formatSessionBlock($currentGroup);
                $currentGroup = collect([$curr]);
            }
        }

        $blocks[] = self::formatSessionBlock($currentGroup);

        return $blocks;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, self>  $group
     * @return array{sessions: \Illuminate\Support\Collection<int, self>, session_id: int|null, start_time: string, end_time: string}
     */
    private static function formatSessionBlock(\Illuminate\Support\Collection $group): array
    {
        return [
            'sessions' => $group,
            'session_id' => $group->count() === 1 ? $group->first()->id : null,
            'start_time' => substr((string) $group->first()->start_time, 0, 5),
            'end_time' => substr((string) $group->last()->end_time, 0, 5),
        ];
    }
}
