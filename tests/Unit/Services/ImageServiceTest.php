<?php

namespace Tests\Unit\Services;

use App\Contracts\FileServiceInterface;
use App\Events\ImageDeleted;
use App\Events\ImageUploaded;
use App\Jobs\CompressImageJob;
use App\Models\File;
use App\Models\Image;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImageService $service;
    private FileServiceInterface $fileServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileServiceMock = $this->mock(FileServiceInterface::class);
        $this->service = new ImageService($this->fileServiceMock);
    }

    private function createUser(): User
    {
        return User::factory()->create(['verified_at' => now()]);
    }

    private function createFileRecord(string $hashSeed = 'test', bool $compressed = false): File
    {
        return File::create([
            'hash' => hash('sha256', $hashSeed),
            'path' => 'images/ab/cd/' . $hashSeed . '.jpg',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'original_extension' => 'jpg',
            'size' => 1024,
            'is_compressed' => $compressed,
            'reference_count' => 1,
        ]);
    }

    // --- upload ---

    public function test_upload_creates_image_and_dispatches_job(): void
    {
        Bus::fake([CompressImageJob::class]);
        Event::fake([ImageUploaded::class]);

        $user = $this->createUser();
        $file = $this->createFileRecord();
        $uploaded = UploadedFile::fake()->image('vacation.jpg');

        $this->fileServiceMock
            ->shouldReceive('storeOrFind')
            ->once()
            ->with($uploaded)
            ->andReturn($file);

        $image = $this->service->upload($uploaded, $user);

        $this->assertEquals($user->id, $image->user_id);
        $this->assertEquals($file->id, $image->file_id);
        $this->assertEquals('vacation.jpg', $image->original_name);

        Bus::assertDispatched(CompressImageJob::class, fn ($job) => true);
        Event::assertDispatched(ImageUploaded::class, fn ($e) => $e->image->id === $image->id && $e->user->id === $user->id);
    }

    public function test_upload_skips_compression_for_already_compressed_file(): void
    {
        Bus::fake([CompressImageJob::class]);
        Event::fake([ImageUploaded::class]);

        $user = $this->createUser();
        $file = $this->createFileRecord('compressed', compressed: true);
        $uploaded = UploadedFile::fake()->image('photo.jpg');

        $this->fileServiceMock
            ->shouldReceive('storeOrFind')
            ->once()
            ->andReturn($file);

        $this->service->upload($uploaded, $user);

        Bus::assertNotDispatched(CompressImageJob::class);
    }

    // --- listForUser ---

    public function test_list_returns_only_user_images(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $file = $this->createFileRecord();

        Image::create(['user_id' => $user->id, 'file_id' => $file->id, 'original_name' => 'mine.jpg']);
        Image::create(['user_id' => $otherUser->id, 'file_id' => $file->id, 'original_name' => 'theirs.jpg']);

        $result = $this->service->listForUser($user, new \App\DTOs\Image\ImageFilterDTO());

        $this->assertCount(1, $result->items());
        $this->assertEquals('mine.jpg', $result->items()[0]->original_name);
    }

    public function test_list_filters_by_original_name(): void
    {
        $user = $this->createUser();
        $file = $this->createFileRecord();

        Image::create(['user_id' => $user->id, 'file_id' => $file->id, 'original_name' => 'vacation_beach.jpg']);
        Image::create(['user_id' => $user->id, 'file_id' => $file->id, 'original_name' => 'work_report.jpg']);

        $filters = new \App\DTOs\Image\ImageFilterDTO(originalName: 'vacation');
        $result = $this->service->listForUser($user, $filters);

        $this->assertCount(1, $result->items());
    }

    public function test_list_filters_by_mime_type(): void
    {
        $user = $this->createUser();
        $jpegFile = $this->createFileRecord('jpeg');

        $pngFile = File::create([
            'hash' => hash('sha256', 'png'),
            'path' => 'images/ab/cd/png.png',
            'disk' => 'public',
            'mime_type' => 'image/png',
            'original_extension' => 'png',
            'size' => 2048,
            'is_compressed' => false,
            'reference_count' => 1,
        ]);

        Image::create(['user_id' => $user->id, 'file_id' => $jpegFile->id, 'original_name' => 'photo.jpg']);
        Image::create(['user_id' => $user->id, 'file_id' => $pngFile->id, 'original_name' => 'logo.png']);

        $filters = new \App\DTOs\Image\ImageFilterDTO(mimeType: 'image/png');
        $result = $this->service->listForUser($user, $filters);

        $this->assertCount(1, $result->items());
        $this->assertEquals('logo.png', $result->items()[0]->original_name);
    }

    public function test_list_respects_pagination(): void
    {
        $user = $this->createUser();
        $file = $this->createFileRecord();

        for ($i = 0; $i < 5; $i++) {
            Image::create(['user_id' => $user->id, 'file_id' => $file->id, 'original_name' => "img{$i}.jpg"]);
        }

        $filters = new \App\DTOs\Image\ImageFilterDTO(perPage: 2);
        $result = $this->service->listForUser($user, $filters);

        $this->assertCount(2, $result->items());
        $this->assertEquals(5, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    // --- delete ---

    public function test_delete_removes_image_and_decrements_file(): void
    {
        Event::fake([ImageDeleted::class]);

        $user = $this->createUser();
        $file = $this->createFileRecord();

        $image = Image::create([
            'user_id' => $user->id,
            'file_id' => $file->id,
            'original_name' => 'remove_me.jpg',
        ]);
        $image->load(['file', 'user']);

        $this->fileServiceMock
            ->shouldReceive('decrementReference')
            ->once()
            ->with(\Mockery::on(fn ($f) => $f->id === $file->id));

        $this->service->delete($image);

        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Event::assertDispatched(ImageDeleted::class, fn ($e) => $e->imageId === $image->id
            && $e->originalName === 'remove_me.jpg'
            && $e->user->id === $user->id
        );
    }
}
