<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status_repair' => [
                'required',
                'string',
                Rule::in(['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki']),
            ],
            'repair_notes' => ['nullable', 'string', 'max:1000'],
            'repaired_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'status_repair.required' => 'Status perkembangan perbaikan wajib dipilih.',
            'status_repair.in' => 'Status perbaikan harus salah satu dari: Belum Diperbaiki, Menunggu Spare Part, atau Sudah Diperbaiki.',
        ];
    }
}
