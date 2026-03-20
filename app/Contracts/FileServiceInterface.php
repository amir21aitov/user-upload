<?php

namespace App\Contracts;

use App\Models\File;
use Illuminate\Http\UploadedFile;

interface FileServiceInterface
{
    public function storeOrFind(UploadedFile $uploadedFile): File;

    public function decrementReference(File $file): void;

    public function getFullPath(File $file): string;

    public function fileExists(File $file): bool;
}
