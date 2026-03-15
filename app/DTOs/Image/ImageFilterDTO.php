<?php

namespace App\DTOs\Image;

final readonly class ImageFilterDTO
{
    public function __construct(
        public int $perPage = 20,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
        public ?string $originalName = null,
        public ?string $mimeType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            perPage: $validated['per_page'] ?? 20,
            sortBy: $validated['sort_by'] ?? 'created_at',
            sortDirection: $validated['sort_direction'] ?? 'desc',
            originalName: $validated['original_name'] ?? null,
            mimeType: $validated['mime_type'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
        );
    }
}
