<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TempMediaValidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => [
                'required',
                'array',
                'max:50',
            ],
            'ids.*' => ['exists:temp_media,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Please provide temp media IDs to validate.',
            'ids.array' => 'Temp media IDs must be provided as an array.',
            'ids.max' => 'Maximum 50 temp media IDs can be validated at once.',
            'ids.*.uuid' => 'Each temp media ID must be a valid UUID.',
            'ids.*.exists' => 'One or more temp media IDs are invalid.',
        ];
    }
}
