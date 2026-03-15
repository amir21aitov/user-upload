<?php

namespace App\Helpers;

use App\Enums\HttpCode;
use App\Enums\HttpStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponseHelper
{
    public static function response(mixed $data, HttpCode $status = HttpCode::OK): JsonResponse
    {
        return response()->json([
            'statusCode' => $status->value,
            'statusDescription' => HttpStatus::status($status),
            'data' => $data,
        ], $status->value);
    }

    public static function paginate(LengthAwarePaginator $paginator, ?string $resourceClass = null): JsonResponse
    {
        $items = $resourceClass && is_subclass_of($resourceClass, JsonResource::class)
            ? $resourceClass::collection($paginator->items())
            : $paginator->items();

        return self::response([
            'pagination' => [
                'current' => $paginator->currentPage(),
                'previous' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : 0,
                'next' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : 0,
                'perPage' => $paginator->perPage(),
                'totalPage' => $paginator->lastPage(),
                'totalItem' => $paginator->total(),
            ],
            'list' => $items,
        ]);
    }
}
