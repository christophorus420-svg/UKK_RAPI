<?php

namespace App\Http\Requests\Concerns;

use App\Models\BookingSession;
use App\Models\Event;
use App\Models\Room;
use Illuminate\Validation\Validator;

trait ValidatesEventSchedule
{
    /**
     * Menyelaraskan start_time & end_time dari session_ids[] (multi-sesi) maupun session_id tunggal.
     */
    protected function prepareScheduleFields(): void
    {
        $sessionIds = array_filter((array) $this->input('session_ids', []), fn ($id) => is_numeric($id));

        if (!empty($sessionIds)) {
            $blocks = BookingSession::groupContiguousIds($sessionIds);
            if (!empty($blocks)) {
                $firstBlock = $blocks[0];
                $lastBlock = $blocks[array_key_last($blocks)];

                $this->merge([
                    'start_time' => $firstBlock['start_time'],
                    'end_time' => $lastBlock['end_time'],
                    'session_id' => count($blocks) === 1 ? $firstBlock['session_id'] : null,
                ]);
                return;
            }
        }

        if ($this->filled('session_id') && (!$this->filled('start_time') || !$this->filled('end_time'))) {
            $session = BookingSession::find($this->session_id);
            if ($session) {
                $this->merge([
                    'start_time' => substr((string) $session->start_time, 0, 5),
                    'end_time' => substr((string) $session->end_time, 0, 5),
                ]);
            }
        }
    }

    /**
     * Aturan validasi jadwal bersama untuk create dan edit peminjaman.
     *
     * @return array<string, mixed>
     */
    protected function baseScheduleRules(?Event $existingEvent = null): array
    {
        $dateRules = ['required', 'date'];
        $existingDate = $existingEvent?->date?->toDateString();

        if (!$existingDate || $this->input('date') !== $existingDate) {
            $dateRules[] = 'after_or_equal:today';
        }

        return [
            'event_name' => ['required', 'string', 'max:150'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => $dateRules,
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'session_ids' => ['nullable', 'array'],
            'session_ids.*' => ['integer', 'exists:booking_sessions,id'],
            'session_id' => ['nullable', 'integer', 'exists:booking_sessions,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia untuk form peminjaman.
     *
     * @return array<string, string>
     */
    protected function baseScheduleMessages(): array
    {
        return [
            'event_name.required' => 'Nama kegiatan / agenda wajib diisi.',
            'event_name.max' => 'Nama kegiatan maksimal 150 karakter.',
            'room_id.required' => 'Ruangan wajib dipilih.',
            'room_id.exists' => 'Ruangan yang dipilih tidak ditemukan dalam sistem.',
            'date.required' => 'Tanggal kegiatan wajib diisi.',
            'date.after_or_equal' => 'Tanggal kegiatan tidak boleh berada di masa lampau.',
            'end_date.after_or_equal' => 'Tanggal selesai kegiatan harus sama atau setelah tanggal mulai.',
            'session_ids.array' => 'Format pilihan sesi tidak valid.',
            'session_ids.*.exists' => 'Salah satu sesi waktu yang dipilih tidak valid.',
            'session_id.exists' => 'Pilihan sesi waktu tidak valid.',
            'start_time.required' => 'Minimal pilih satu sesi atau tentukan jam mulai kegiatan.',
            'end_time.required' => 'Jam selesai kegiatan wajib ditentukan.',
            'end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai kegiatan.',
        ];
    }

    /**
     * Mengembalikan blok-blok waktu terkelompok dari pilihan sesi maupun input jam langsung.
     *
     * @return array<int, array{session_id: int|null, start_time: string, end_time: string}>
     */
    public function resolvedScheduleBlocks(): array
    {
        $sessionIds = array_filter((array) $this->input('session_ids', []), fn ($id) => is_numeric($id));

        if (!empty($sessionIds)) {
            $blocks = BookingSession::groupContiguousIds($sessionIds);
            if (!empty($blocks)) {
                return array_map(fn (array $block) => [
                    'session_id' => $block['session_id'],
                    'start_time' => $block['start_time'],
                    'end_time' => $block['end_time'],
                ], $blocks);
            }
        }

        return [[
            'session_id' => $this->filled('session_id') ? (int) $this->session_id : null,
            'start_time' => (string) $this->start_time,
            'end_time' => (string) $this->end_time,
        ]];
    }

    /**
     * Validasi keaktifan ruangan, kontinuitas sesi saat edit, dan bentrok jadwal terhadap seluruh blok.
     */
    protected function validateRoomAndScheduleConflicts(
        Validator $validator,
        ?Event $existingEvent = null,
        bool $requireSingleContiguousBlock = false
    ): void {
        $validator->after(function (Validator $validator) use ($existingEvent, $requireSingleContiguousBlock) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $room = Room::find($this->room_id);
            if (!$room || !$room->is_active) {
                $validator->errors()->add('room_id', 'Ruangan yang dipilih sedang dinonaktifkan atau dalam renovasi.');
                return;
            }

            $sessionIds = array_filter((array) $this->input('session_ids', []), fn ($id) => is_numeric($id));
            if (!empty($sessionIds)) {
                $grouped = BookingSession::groupContiguousIds($sessionIds);
                if (empty($grouped)) {
                    $validator->errors()->add('session_ids', 'Sesi yang dipilih sedang nonaktif atau tidak tersedia.');
                    return;
                }

                if ($requireSingleContiguousBlock && count($grouped) > 1) {
                    $validator->errors()->add(
                        'session_ids',
                        'Untuk mengedit satu agenda, pilihan beberapa sesi harus berurutan tanpa melewati sesi di tengahnya.'
                    );
                    return;
                }
            }

            $blocks = $this->resolvedScheduleBlocks();
            $startDate = (string) $this->date;
            $endDate = $this->filled('end_date') ? (string) $this->end_date : null;

            foreach ($blocks as $block) {
                $conflictingEvent = Event::findConflict(
                    roomId: (int) $this->room_id,
                    startDate: $startDate,
                    endDate: $endDate,
                    startTime: $block['start_time'],
                    endTime: $block['end_time'],
                    sessionId: $block['session_id'],
                    excludeEventId: $existingEvent?->id
                );

                if ($conflictingEvent) {
                    $jamMulai = substr((string) $conflictingEvent->start_time, 0, 5);
                    $jamSelesai = substr((string) $conflictingEvent->end_time, 0, 5);
                    $tanggalIndo = $conflictingEvent->formatted_date_range;
                    $pesanBentrok = "Jadwal bentrok! Ruangan '{$room->room_name}' sudah dipesan pada {$tanggalIndo} pukul {$jamMulai} - {$jamSelesai} WIB untuk agenda '{$conflictingEvent->event_name}'. Silakan pilih ruangan, tanggal, atau sesi lain.";

                    $errorKey = !empty($sessionIds)
                        ? 'session_ids'
                        : ($this->filled('session_id') ? 'session_id' : 'start_time');

                    $validator->errors()->add($errorKey, $pesanBentrok);
                    return;
                }
            }
        });
    }
}

