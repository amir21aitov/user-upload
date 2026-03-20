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
}
