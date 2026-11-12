<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEventSchedule;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    use ValidatesEventSchedule;

    public function authorize(): bool
    {
        $event = $this->route('event');

        return auth()->check() && $event instanceof Event && $event->canBeEditedBy(auth()->user());
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            redirect()->route('events.index')->with('error', 'Anda tidak memiliki hak untuk mengedit agenda ini.')
        );
    }

    protected function prepareForValidation(): void
    {
        $this->prepareScheduleFields();
    }

    public function rules(): array
    {
        /** @var Event|null $event */
        $event = $this->route('event');

        return $this->baseScheduleRules($event);
    }

    public function messages(): array
    {
        return $this->baseScheduleMessages();
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Event|null $event */
        $event = $this->route('event');

        $this->validateRoomAndScheduleConflicts(
            validator: $validator,
            existingEvent: $event,
            requireSingleContiguousBlock: true
        );
    }
}

