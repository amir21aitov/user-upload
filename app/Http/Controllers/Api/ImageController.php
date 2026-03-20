<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Image\ImageIndexRequest;
use App\Http\Requests\Image\UploadImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Image;
use App\Services\ImageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ImageController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ImageService $imageService,
    ) {}

    public function upload(UploadImageRequest $request): JsonResponse
    {
        $image = $this->imageService->upload(
            $request->file('image'),
            $request->user(),
        );

        return response()->json([
            'data' => new ImageResource($image),
        ], Response::HTTP_CREATED);
    }

    public function index(ImageIndexRequest $request): JsonResponse
    {
        $paginator = $this->imageService->listForUser(
            $request->user(),
            $request->toDTO(),
        );

        return ImageResource::collection($paginator)->response();
    }

    public function show(Image $image): JsonResponse
    {
        $this->authorize('view', $image);

        $image->load('file');

        return response()->json([
            'data' => new ImageResource($image),
        ]);
    }

    public function destroy(Image $image): JsonResponse
    {
        $this->authorize('delete', $image);

        $this->imageService->delete($image);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
