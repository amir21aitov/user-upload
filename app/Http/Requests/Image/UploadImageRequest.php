<?php

namespace App\Http\Requests\Image;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => 'Only JPEG and PNG formats are allowed.',
            'image.max' => 'Image size must not exceed 5 MB.',
        ];
    }
}
