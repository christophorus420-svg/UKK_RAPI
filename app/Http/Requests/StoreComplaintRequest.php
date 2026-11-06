<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.required' => 'Ruangan lokasi fasilitas rusak wajib dipilih.',
            'room_id.exists' => 'Ruangan yang dipilih tidak ditemukan dalam sistem.',
            'description.required' => 'Deskripsi kerusakan fisik sarpras wajib diisi.',
            'description.min' => 'Deskripsi kerusakan minimal 10 karakter agar memudahkan PIC melakukan perbaikan.',
            'description.max' => 'Deskripsi kerusakan maksimal 1000 karakter.',
            'photo.image' => 'File bukti fisik harus berupa file gambar.',
            'photo.mimes' => 'Format foto yang didukung adalah JPEG, PNG, JPG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal adalah 5 Megabyte (5120 KB).',
        ];
    }
}
