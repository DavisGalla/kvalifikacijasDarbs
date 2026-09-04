<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_time' => ['required', 'date_format:Y-m-d\TH:i'],
            'end_time' => ['required', 'date_format:Y-m-d\TH:i', 'after:start_time'],
        ];
    }
}
