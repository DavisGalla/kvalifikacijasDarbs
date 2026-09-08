<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date', 'after:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'registration_deadline' => ['required', 'date', 'before:start_time'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
