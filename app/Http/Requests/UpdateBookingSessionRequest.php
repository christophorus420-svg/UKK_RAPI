<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->filled('start_time')) {
            $merge['start_time'] = substr($this->start_time, 0, 5);
        }
        if ($this->filled('end_time')) {
            $merge['end_time'] = substr($this->end_time, 0, 5);
        }
        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'session_name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_active' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_name.required' => 'Nama sesi peminjaman wajib diisi.',
            'session_name.max' => 'Nama sesi peminjaman maksimal 100 karakter.',
            'start_time.required' => 'Jam mulai sesi wajib diisi.',
            'start_time.date_format' => 'Format jam mulai tidak valid (HH:MM).',
            'end_time.required' => 'Jam selesai sesi wajib diisi.',
            'end_time.date_format' => 'Format jam selesai tidak valid (HH:MM).',
            'end_time.after' => 'Jam selesai harus lebih akhir dari jam mulai sesi.',
        ];
    }
}
