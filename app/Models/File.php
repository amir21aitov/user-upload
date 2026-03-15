<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class File extends Model
{
    protected $fillable = [
        'hash',
        'path',
        'disk',
        'mime_type',
        'original_extension',
        'size',
        'compressed_size',
        'is_compressed',
        'reference_count',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'compressed_size' => 'integer',
            'is_compressed' => 'boolean',
            'reference_count' => 'integer',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }
}
