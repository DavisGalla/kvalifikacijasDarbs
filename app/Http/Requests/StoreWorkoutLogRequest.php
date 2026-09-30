<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'exercise' => ['required', 'string', 'max:100'],
            'set_weights' => ['required', 'array', 'min:1', 'max:100'],
            'set_weights.*' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'performed_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }
}
