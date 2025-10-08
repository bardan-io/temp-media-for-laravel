<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TempMediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = config('temp-media.max_file_size', 10 * 1024 * 1024);
        $allowedMimeTypes = config('temp-media.allowed_mime_types', []);

        $mimeTypesRule = ! empty($allowedMimeTypes)
            ? 'mimes:'.implode(',', array_map(fn ($type) => explode('/', $type)[1], $allowedMimeTypes))
            : '';

        return [
            'file' => array_filter([
                'required',
                'file',
                'image',
                'max:'.($maxSize / 1024),
                $mimeTypesRule,
            ]),
        ];
    }

    public function messages(): array
    {
        $maxSizeMB = config('temp-media.max_file_size', 10 * 1024 * 1024) / (1024 * 1024);
        $allowedTypes = config('temp-media.allowed_mime_types', []);
        $typesList = implode(', ', array_map(fn ($type) => strtoupper(explode('/', $type)[1]), $allowedTypes));

        return [
            'file.required' => 'Please select a file to upload.',
            'file.image' => 'The file must be an image.',
            'file.max' => "The file size must not exceed {$maxSizeMB}MB.",
            'file.mimes' => "The file must be one of the following types: {$typesList}.",
        ];
    }
}
