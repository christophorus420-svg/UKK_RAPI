<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'otp' => ['nullable', 'string'],
            'token' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->filled('otp') && !$this->filled('token')) {
                $validator->errors()->add('otp', 'Kode OTP verifikasi wajib diisi.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username tidak valid.',
            'otp.required' => 'Kode OTP verifikasi wajib diisi.',
            'token.required' => 'Token reset password tidak ditemukan.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan password baru.',
            'password_confirmation.required' => 'Konfirmasi password baru wajib diisi.',
        ];
    }
}
