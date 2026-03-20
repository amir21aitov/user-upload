<?php

namespace Tests\Feature;

use App\Jobs\CompressImageJob;
use App\Models\File;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'verified_at' => now(),
            'password' => 'password',
        ]);

        $this->token = auth('api')->login($this->user);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function createFileRecord(string $hashSeed = 'test', string $name = 'test.jpg'): File
    {
        return File::create([
            'hash' => hash('sha256', $hashSeed),
            'path' => 'images/ab/cd/' . $name,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'original_extension' => 'jpg',
            'size' => 1024,
            'is_compressed' => false,
            'reference_count' => 1,
        ]);
    }

    public function test_user_can_upload_image(): void
    {
        Bus::fake([CompressImageJob::class]);

        $storageMock = Storage::partialMock();
        $storageMock->shouldReceive('disk->putFileAs')->once()->andReturn('images/ab/cd/test.jpg');
        $storageMock->shouldReceive('disk->url')->andReturn('http://localhost/storage/images/ab/cd/test.jpg');

        $file = UploadedFile::fake()->image('photo.jpg', 640, 480);

        $response = $this->postJson('/api/images', [
            'image' => $file,
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'original_name', 'created_at']]);

        $this->assertDatabaseHas('images', [
            'user_id' => $this->user->id,
            'original_name' => 'photo.jpg',
        ]);

        Bus::assertDispatched(CompressImageJob::class);
    }

    public function test_user_can_list_own_images(): void
    {
        $file = $this->createFileRecord();

        Image::create([
            'user_id' => $this->user->id,
            'file_id' => $file->id,
            'original_name' => 'photo.jpg',
        ]);

        $response = $this->getJson('/api/images', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_view_own_image(): void
    {
        $file = $this->createFileRecord();

        $image = Image::create([
            'user_id' => $this->user->id,
            'file_id' => $file->id,
            'original_name' => 'photo.jpg',
        ]);

        $response = $this->getJson("/api/images/{$image->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.original_name', 'photo.jpg');
    }

    public function test_user_cannot_view_others_image(): void
    {
        $otherUser = User::factory()->create(['verified_at' => now()]);
        $file = $this->createFileRecord('other', 'other.jpg');

        $image = Image::create([
            'user_id' => $otherUser->id,
            'file_id' => $file->id,
            'original_name' => 'secret.jpg',
        ]);

        $response = $this->getJson("/api/images/{$image->id}", $this->authHeaders());

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_image(): void
    {
        $storageMock = Storage::partialMock();
        $storageMock->shouldReceive('disk->delete')->once()->andReturn(true);

        $file = $this->createFileRecord('deleteme', 'deleteme.jpg');

        $image = Image::create([
            'user_id' => $this->user->id,
            'file_id' => $file->id,
            'original_name' => 'deleteme.jpg',
        ]);

        $response = $this->deleteJson("/api/images/{$image->id}", [], $this->authHeaders());

        $response->assertStatus(204);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_user_cannot_delete_others_image(): void
    {
        $otherUser = User::factory()->create(['verified_at' => now()]);
        $file = $this->createFileRecord('nope', 'nope.jpg');

        $image = Image::create([
            'user_id' => $otherUser->id,
            'file_id' => $file->id,
            'original_name' => 'nope.jpg',
        ]);

        $response = $this->deleteJson("/api/images/{$image->id}", [], $this->authHeaders());

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_images(): void
    {
        $response = $this->getJson('/api/images');

        $response->assertStatus(401);
    }

    public function test_index_validates_filters(): void
    {
        $response = $this->getJson('/api/images?' . http_build_query([
            'sort_by' => 'invalid_field',
            'per_page' => 999,
        ]), $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_index_filters_by_name(): void
    {
        $file = $this->createFileRecord('filter', 'filter.jpg');

        Image::create([
            'user_id' => $this->user->id,
            'file_id' => $file->id,
            'original_name' => 'vacation_photo.jpg',
        ]);

        Image::create([
            'user_id' => $this->user->id,
            'file_id' => $file->id,
            'original_name' => 'work_document.jpg',
        ]);

        $response = $this->getJson('/api/images?' . http_build_query([
            'original_name' => 'vacation',
        ]), $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
