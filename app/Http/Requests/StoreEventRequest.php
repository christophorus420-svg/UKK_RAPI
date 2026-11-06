<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('session_id') && (!$this->filled('start_time') || !$this->filled('end_time'))) {
            $session = \App\Models\BookingSession::find($this->session_id);
            if ($session) {
                $this->merge([
                    'start_time' => substr($session->start_time, 0, 5),
                    'end_time' => substr($session->end_time, 0, 5),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'event_name' => ['required', 'string', 'max:150'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'session_id' => ['nullable', 'integer', 'exists:booking_sessions,id'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_name.required' => 'Nama kegiatan / agenda wajib diisi.',
            'event_name.max' => 'Nama kegiatan maksimal 150 karakter.',
            'room_id.required' => 'Ruangan wajib dipilih.',
            'room_id.exists' => 'Ruangan yang dipilih tidak ditemukan dalam sistem.',
            'date.required' => 'Tanggal kegiatan wajib diisi.',
            'date.after_or_equal' => 'Tanggal kegiatan tidak boleh berada di masa lampau.',
            'end_date.after_or_equal' => 'Tanggal selesai kegiatan harus sama atau setelah tanggal mulai.',
            'session_id.exists' => 'Pilihan sesi waktu tidak valid.',
            'start_time.required' => 'Jam mulai kegiatan wajib ditentukan.',
            'end_time.required' => 'Jam selesai kegiatan wajib ditentukan.',
            'end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai kegiatan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Pastikan ruangan dalam keadaan aktif
            $room = Room::find($this->room_id);
            if (!$room || !$room->is_active) {
                $validator->errors()->add('room_id', 'Ruangan yang dipilih sedang dinonaktifkan atau dalam renovasi.');
                return;
            }

            // LOGIKA VALIDASI TABRAKAN WAKTU / SESI (CONFLICT PREVENTION - Single & Multi-Day)
            $startDate = $this->date;
            $endDate = $this->filled('end_date') ? $this->end_date : $this->date;

            $conflictQuery = Event::where('room_id', $this->room_id)
                ->whereIn('status', ['approved', 'pending'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->where('date', '<=', $endDate)
                      ->whereRaw('COALESCE(end_date, date) >= ?', [$startDate]);
                });

            if ($this->filled('session_id')) {
                $conflictQuery->where(function ($q) {
                    $q->where('session_id', $this->session_id)
                      ->orWhere(function ($sub) {
                          $sub->where('start_time', '<', $this->end_time)
                              ->where('end_time', '>', $this->start_time);
                      });
                });
            } else {
                $conflictQuery->where('start_time', '<', $this->end_time)
                              ->where('end_time', '>', $this->start_time);
            }

            $conflictingEvent = $conflictQuery->first();

            if ($conflictingEvent) {
                $jamMulai = substr($conflictingEvent->start_time, 0, 5);
                $jamSelesai = substr($conflictingEvent->end_time, 0, 5);
                $tanggalIndo = $conflictingEvent->formatted_date_range;
                $pesanBentrok = "Jadwal bentrok! Ruangan '{$room->room_name}' sudah dipesan pada {$tanggalIndo} pukul {$jamMulai} - {$jamSelesai} WIB untuk agenda '{$conflictingEvent->event_name}'. Silakan pilih ruangan, tanggal, atau sesi lain.";

                if ($this->filled('session_id')) {
                    $validator->errors()->add('session_id', $pesanBentrok);
                } else {
                    $validator->errors()->add('start_time', $pesanBentrok);
                }
            }
        });
    }
}
