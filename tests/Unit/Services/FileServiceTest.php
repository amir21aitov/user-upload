<?php

namespace Tests\Unit\Services;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileServiceTest extends TestCase
{
    use RefreshDatabase;

    private FileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FileService::class);
    }

    public function test_store_creates_new_file_record(): void
    {
        $storageMock = Storage::partialMock();
        $storageMock->shouldReceive('disk->putFileAs')->once()->andReturn('images/ab/cd/hash.jpg');

        $uploaded = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $file = $this->service->storeOrFind($uploaded);

        $this->assertDatabaseHas('files', ['id' => $file->id]);
        $this->assertEquals(1, $file->reference_count);
        $this->assertFalse($file->is_compressed);
        $this->assertEquals('image/jpeg', $file->mime_type);
    }

    public function test_store_deduplicates_identical_files(): void
    {
        $storageMock = Storage::partialMock();
        $storageMock->shouldReceive('disk->putFileAs')->once()->andReturn('images/ab/cd/hash.jpg');

        $uploaded1 = UploadedFile::fake()->image('photo1.jpg', 100, 100);
        $uploaded2 = UploadedFile::fake()->createWithContent(
            'photo2.jpg',
            file_get_contents($uploaded1->getRealPath()),
        );

        $file1 = $this->service->storeOrFind($uploaded1);
        $file2 = $this->service->storeOrFind($uploaded2);

        $this->assertEquals($file1->id, $file2->id);
        $this->assertEquals(2, $file2->fresh()->reference_count);
        $this->assertDatabaseCount('files', 1);
    }

    public function test_decrement_reference_deletes_file_at_zero(): void
    {
        $storageMock = Storage::partialMock();
        $storageMock->shouldReceive('disk->delete')->once()->andReturn(true);

        $file = File::create([
            'hash' => hash('sha256', 'test'),
            'path' => 'images/ab/cd/test.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'original_extension' => 'jpg',
            'size' => 1024,
            'is_compressed' => false,
            'reference_count' => 1,
        ]);

        $this->service->decrementReference($file);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_decrement_reference_keeps_file_with_remaining_refs(): void
    {
        $file = File::create([
            'hash' => hash('sha256', 'multi'),
            'path' => 'images/ab/cd/multi.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'original_extension' => 'jpg',
            'size' => 1024,
            'is_compressed' => false,
            'reference_count' => 3,
        ]);

        $this->service->decrementReference($file);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'reference_count' => 2,
        ]);
    }

    public function test_decrement_reference_handles_missing_file_gracefully(): void
    {
        $file = File::create([
            'hash' => hash('sha256', 'ghost'),
            'path' => 'images/ab/cd/ghost.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'original_extension' => 'jpg',
            'size' => 1024,
            'is_compressed' => false,
            'reference_count' => 1,
        ]);

        $file->delete();

        $this->service->decrementReference($file);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}
