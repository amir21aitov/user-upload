<?php

namespace App\Services;

use App\Contracts\FileServiceInterface;
use App\Contracts\ImageServiceInterface;
use App\DTOs\Image\ImageFilterDTO;
use App\Events\ImageDeleted;
use App\Events\ImageUploaded;
use App\Jobs\CompressImageJob;
use App\Models\Image;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageService implements ImageServiceInterface
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    public function upload(UploadedFile $uploadedFile, User $user): Image
    {
        $file = $this->fileService->storeOrFind($uploadedFile);

        $image = Image::query()->create([
            'user_id' => $user->id,
            'file_id' => $file->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
        ]);

        if (!$file->is_compressed) {
            CompressImageJob::dispatch($file->id);
        }

        Log::info('Image uploaded', [
            'image_id' => $image->id,
            'user_id' => $user->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'file_id' => $file->id,
        ]);

        ImageUploaded::dispatch($image, $user);

        return $image->load('file');
    }

    public function listForUser(User $user, ImageFilterDTO $filters): LengthAwarePaginator
    {
        $query = Image::query()->with('file')->where('user_id', $user->id);

        if ($filters->originalName) {
            $query->where('original_name', 'like', "%{$filters->originalName}%");
        }

        if ($filters->mimeType) {
            $query->whereHas('file', fn ($q) => $q->where('mime_type', $filters->mimeType));
        }

        if ($filters->dateFrom) {
            $query->whereDate('created_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate('created_at', '<=', $filters->dateTo);
        }

        if ($filters->sortBy === 'size') {
            $query->joinSub(
                \App\Models\File::query()->select('id', 'size'),
                'files_sort',
                'files_sort.id',
                'images.file_id',
            )->orderBy('files_sort.size', $filters->sortDirection);
        } else {
            $sortColumn = match ($filters->sortBy) {
                'original_name' => 'original_name',
                default => 'created_at',
            };
            $query->orderBy($sortColumn, $filters->sortDirection);
        }

        return $query->paginate($filters->perPage);
    }

    public function delete(Image $image): void
    {
        $imageId = $image->id;
        $originalName = $image->original_name;
        $user = $image->user;
        $file = $image->file;

        $image->delete();
        $this->fileService->decrementReference($file);

        Log::info('Image deleted', [
            'image_id' => $imageId,
            'user_id' => $user->id,
            'original_name' => $originalName,
        ]);

        ImageDeleted::dispatch($imageId, $originalName, $user);
    }
}
