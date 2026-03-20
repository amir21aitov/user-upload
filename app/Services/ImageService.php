<?php

namespace App\Services;

use App\DTOs\Image\ImageFilterDTO;
use App\Jobs\CompressImageJob;
use App\Models\Image;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ImageService
{
    public function __construct(
        private readonly FileService $fileService,
    ) {}

    public function upload(UploadedFile $uploadedFile, User $user): Image
    {
        $file = $this->fileService->storeOrFind($uploadedFile);

        $image = Image::create([
            'user_id' => $user->id,
            'file_id' => $file->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
        ]);

        if (!$file->is_compressed) {
            CompressImageJob::dispatch($file->id);
        }

        return $image->load('file');
    }

    public function listForUser(User $user, ImageFilterDTO $filters): LengthAwarePaginator
    {
        $query = Image::with('file')->where('user_id', $user->id);

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
                \App\Models\File::select('id', 'size'),
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
        $file = $image->file;
        $image->delete();
        $this->fileService->decrementReference($file);
    }
}
