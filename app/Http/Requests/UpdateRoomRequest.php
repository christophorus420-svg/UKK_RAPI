<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $rolePicId = Role::where('role_name', 'pic')->value('id');
        $room = $this->route('room');
        $roomId = is_object($room) ? $room->id : $room;

        return [
            'room_name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'room_name')->ignore($roomId)],
            'capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'location' => ['required', 'string', 'max:100'],
            'pic_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('role_id', $rolePicId)),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_name.required' => 'Nama ruangan wajib diisi.',
            'room_name.unique' => 'Nama ruangan sudah terdaftar di sistem.',
            'capacity.required' => 'Kapasitas ruangan wajib diisi.',
            'capacity.integer' => 'Kapasitas ruangan harus berupa angka bilangan bulat.',
            'capacity.min' => 'Kapasitas ruangan minimal untuk 1 orang.',
            'location.required' => 'Lokasi ruangan wajib diisi.',
            'pic_id.required' => 'Petugas PIC penanggung jawab ruangan wajib dipilih.',
            'pic_id.exists' => 'Pengguna yang dipilih tidak terdaftar sebagai staf PIC Ruang.',
        ];
    }
}
