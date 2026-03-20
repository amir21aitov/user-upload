<?php

namespace App\Services;

use App\Contracts\FileServiceInterface;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileService implements FileServiceInterface
{
    public function storeOrFind(UploadedFile $uploadedFile): File
    {
        $hash = hash_file('sha256', $uploadedFile->getRealPath());

        return DB::transaction(function () use ($uploadedFile, $hash) {
            $existing = File::query()->where('hash', $hash)->lockForUpdate()->first();

            if ($existing) {
                $existing->increment('reference_count');
                Log::info('File deduplicated, reference incremented', [
                    'file_id' => $existing->id,
                    'hash' => $hash,
                    'reference_count' => $existing->reference_count + 1,
                ]);
                return $existing;
            }

            $path = $this->generatePath($hash, $uploadedFile->getClientOriginalExtension());

            Storage::disk('public')->putFileAs(
                dirname($path),
                $uploadedFile,
                basename($path),
            );

            $file = File::query()->create([
                'hash' => $hash,
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $uploadedFile->getMimeType(),
                'original_extension' => strtolower($uploadedFile->getClientOriginalExtension()),
                'size' => $uploadedFile->getSize(),
                'is_compressed' => false,
                'reference_count' => 1,
            ]);

            Log::info('New file stored', [
                'file_id' => $file->id,
                'hash' => $hash,
                'path' => $path,
                'size' => $uploadedFile->getSize(),
            ]);

            return $file;
        });
    }

    public function decrementReference(File $file): void
    {
        DB::transaction(function () use ($file) {
            $file = File::query()->lockForUpdate()->find($file->id);

            if (!$file) {
                return;
            }

            $file->decrement('reference_count');

            if ($file->reference_count <= 0) {
                Storage::disk($file->disk)->delete($file->path);
                $file->delete();
                Log::info('File removed (no remaining references)', [
                    'file_id' => $file->id,
                    'hash' => $file->hash,
                    'path' => $file->path,
                ]);
            } else {
                Log::info('File reference decremented', [
                    'file_id' => $file->id,
                    'reference_count' => $file->reference_count,
                ]);
            }
        });
    }

    public function getFullPath(File $file): string
    {
        return Storage::disk($file->disk)->path($file->path);
    }

    public function fileExists(File $file): bool
    {
        return Storage::disk($file->disk)->exists($file->path);
    }

    private function generatePath(string $hash, string $extension): string
    {
        $dir1 = substr($hash, 0, 2);
        $dir2 = substr($hash, 2, 2);
        $ext = strtolower($extension);

        return "images/{$dir1}/{$dir2}/{$hash}.{$ext}";
    }
}
