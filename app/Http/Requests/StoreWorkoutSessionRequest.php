<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'day_name' => ['nullable', 'string', 'max:50'],
            'performed_on' => ['nullable', 'date', 'before_or_equal:today'],
            'exercises' => ['required', 'array', 'min:1', 'max:30'],
            'exercises.*.name' => ['required', 'string', 'max:100'],
            'exercises.*.set_weights' => ['nullable', 'array', 'max:100'],
            'exercises.*.set_weights.*' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}
