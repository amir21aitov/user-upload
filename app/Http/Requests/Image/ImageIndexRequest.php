<?php

namespace App\Http\Requests\Image;

use App\DTOs\Image\ImageFilterDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImageIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['sometimes', 'string', Rule::in(['created_at', 'original_name', 'size'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'original_name' => ['sometimes', 'string', 'max:255'],
            'mime_type' => ['sometimes', 'string', Rule::in(['image/jpeg', 'image/png'])],
            'date_from' => ['sometimes', 'date', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }

    public function toDTO(): ImageFilterDTO
    {
        return new ImageFilterDTO(
            perPage: $this->validated('per_page', 20),
            sortBy: $this->validated('sort_by', 'created_at'),
            sortDirection: $this->validated('sort_direction', 'desc'),
            originalName: $this->validated('original_name'),
            mimeType: $this->validated('mime_type'),
            dateFrom: $this->validated('date_from'),
            dateTo: $this->validated('date_to'),
        );
    }
}
