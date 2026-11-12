<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEventSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEventRequest extends FormRequest
{
    use ValidatesEventSchedule;

    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->prepareScheduleFields();
    }

    public function rules(): array
    {
        return $this->baseScheduleRules();
    }

    public function messages(): array
    {
        return $this->baseScheduleMessages();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateRoomAndScheduleConflicts($validator);
    }
}
