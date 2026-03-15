<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->whenLoaded('file', fn () => $this->file->mime_type),
            'size' => $this->whenLoaded('file', fn () => $this->file->size),
            'is_compressed' => $this->whenLoaded('file', fn () => $this->file->is_compressed),
            'url' => $this->whenLoaded('file', fn () => Storage::disk($this->file->disk)->url($this->file->path)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
