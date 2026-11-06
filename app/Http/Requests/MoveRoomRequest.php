<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MoveRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'new_room_id' => ['required', 'integer', 'exists:rooms,id'],
            'move_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_room_id.required' => 'Ruangan tujuan baru wajib dipilih.',
            'new_room_id.exists' => 'Ruangan tujuan tidak terdaftar dalam sistem.',
            'move_reason.required' => 'Alasan pemindahan ruangan wajib diisi secara transparan.',
            'move_reason.min' => 'Alasan pemindahan minimal 5 karakter agar dapat dipahami oleh pemohon.',
            'move_reason.max' => 'Alasan pemindahan maksimal 500 karakter.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $event = $this->route('event');
            if (!$event instanceof Event) {
                $event = Event::findOrFail($event);
            }

            // Pastikan tidak memilih ruangan yang sama dengan saat ini
            if ((int) $this->new_room_id === (int) $event->room_id) {
                $validator->errors()->add('new_room_id', 'Ruangan baru harus berbeda dari ruangan saat ini.');
                return;
            }

            // Pastikan ruangan tujuan baru dalam status aktif
            $targetRoom = Room::find($this->new_room_id);
            if (!$targetRoom || !$targetRoom->is_active) {
                $validator->errors()->add('new_room_id', 'Ruangan tujuan sedang dinonaktifkan atau dalam renovasi.');
                return;
            }

            // Validasi tabrakan waktu: ruangan baru tidak boleh bentrok pada jam dan tanggal event tersebut
            $conflict = Event::where('room_id', $this->new_room_id)
                ->where('date', $event->date)
                ->where('id', '!=', $event->id)
                ->whereIn('status', ['approved', 'pending'])
                ->where('start_time', '<', $event->end_time)
                ->where('end_time', '>', $event->start_time)
                ->first();

            if ($conflict) {
                $jamMulai = substr($conflict->start_time, 0, 5);
                $jamSelesai = substr($conflict->end_time, 0, 5);

                $validator->errors()->add(
                    'new_room_id',
                    "Ruangan tujuan '{$targetRoom->room_name}' tidak dapat digunakan karena telah dipesan pada jam tersebut untuk kegiatan '{$conflict->event_name}' ({$jamMulai} - {$jamSelesai} WIB)."
                );
            }
        });
    }
}
