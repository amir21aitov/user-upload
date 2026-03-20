<?php

namespace App\Jobs;

use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompressImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly int $fileId,
    ) {}

    public function handle(): void
    {
        $file = File::query()->find($this->fileId);

        if (!$file || $file->is_compressed) {
            return;
        }

        $disk = Storage::disk($file->disk);
        $fullPath = $disk->path($file->path);

        if (!file_exists($fullPath)) {
            Log::warning("CompressImageJob: file not found at {$fullPath}");
            return;
        }

        $image = match ($file->mime_type) {
            'image/jpeg' => imagecreatefromjpeg($fullPath),
            'image/png' => imagecreatefrompng($fullPath),
            default => null,
        };

        if (!$image) {
            Log::warning("CompressImageJob: unsupported mime type {$file->mime_type}");
            return;
        }

        $tempPath = $fullPath . '.tmp';

        $result = match ($file->mime_type) {
            'image/jpeg' => imagejpeg($image, $tempPath, 85),
            'image/png' => $this->compressPng($image, $tempPath),
            default => false,
        };

        imagedestroy($image);

        if (!$result || !file_exists($tempPath)) {
            @unlink($tempPath);
            return;
        }

        $compressedSize = filesize($tempPath);

        if ($compressedSize < $file->size) {
            rename($tempPath, $fullPath);
            $file->update([
                'compressed_size' => $compressedSize,
                'is_compressed' => true,
            ]);
        } else {
            @unlink($tempPath);
            $file->update([
                'compressed_size' => $file->size,
                'is_compressed' => true,
            ]);
        }
    }

    private function compressPng(\GdImage $image, string $path): bool
    {
        imagesavealpha($image, true);
        return imagepng($image, $path, 6);
    }
}
