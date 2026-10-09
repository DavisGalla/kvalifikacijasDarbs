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
            // A deadline already in the past would publish a competition nobody can register for.
            'registration_deadline' => ['required', 'date', 'after:now', 'before:start_time'],
            'max_participants' => ['nullable', 'integer', 'min:1'],
            'registration_mode' => ['required', Rule::in(['individual', 'team'])],
            'min_team_members' => ['nullable', 'integer', 'min:1', 'required_if:registration_mode,team'],
            'max_team_members' => ['nullable', 'integer', 'min:1', 'gte:min_team_members', 'required_if:registration_mode,team'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }
}
