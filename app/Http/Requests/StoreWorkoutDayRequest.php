<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** The form sends exercises as a textarea, one per line. */
    protected function prepareForValidation(): void
    {
        $lines = is_string($this->input('exercises'))
            ? preg_split('/\R/', $this->input('exercises'))
            : (array) $this->input('exercises');

        $this->merge([
            'exercises' => collect($lines)
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'exercises' => ['required', 'array', 'min:1', 'max:30'],
            'exercises.*' => ['string', 'max:100'],
        ];
    }
}
