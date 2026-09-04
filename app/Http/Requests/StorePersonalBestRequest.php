<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalBestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'exercise' => ['required', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }
}
