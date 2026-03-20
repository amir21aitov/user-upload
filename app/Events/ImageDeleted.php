<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImageDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $imageId,
        public readonly string $originalName,
        public readonly User $user,
    ) {}
}
