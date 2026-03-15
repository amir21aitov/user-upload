<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Image\ImageFilterDTO;
use App\Enums\HttpCode;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function upload(UploadImageRequest $request): JsonResponse
    {
        $image = $this->imageService->upload(
            $request->file('image'),
            $request->user(),
        );

        return ResponseHelper::response(new ImageResource($image), HttpCode::CREATED);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = ImageFilterDTO::fromRequest($request->all());

        $paginator = $this->imageService->listForUser($request->user(), $filters);

        return ResponseHelper::paginate($paginator, ImageResource::class);
    }

    public function show(Request $request, Image $image): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return ResponseHelper::response(
                ['message' => 'Forbidden'],
                HttpCode::FORBIDDEN,
            );
        }

        $image->load('file');

        return ResponseHelper::response(new ImageResource($image));
    }

    public function destroy(Request $request, Image $image): JsonResponse
    {
        if ($image->user_id !== $request->user()->id) {
            return ResponseHelper::response([
                    'message' => 'Forbidden'
                ], HttpCode::FORBIDDEN,
            );
        }

        $this->imageService->delete($image);

        return ResponseHelper::response(null, HttpCode::NO_CONTENT);
    }
}
