<?php

namespace App\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response as Status;

trait ApiResponse
{
    private function buildResponse(bool $success, int $status, ?string $message = null, mixed $data = null): array
    {
        $isPaginated = $data instanceof ResourceCollection
            && $data->resource instanceof LengthAwarePaginator;

        return [
            'success' => $success,
            'message' => $message,
            'data' => $data ?: null,
            'paginate' => $isPaginated ? [
                'per_page' => $data->resource->perPage(),
                'current_page' => $data->resource->currentPage(),
                'last_page' => $data->resource->lastPage(),
            ] : null,
        ];
    }

    public function success(?string $message, array $data): JsonResponse
    {
        return response()->json(
            $this->buildResponse(true, Status::HTTP_OK, $message, $data),
            Status::HTTP_OK
        );
    }

    public function created(?string $message, array $data): JsonResponse
    {
        return response()->json(
            $this->buildResponse(true, Status::HTTP_CREATED, $message, $data),
            Status::HTTP_CREATED
        );
    }

    public function noContent(): JsonResponse
    {
        return response()->json(
            $this->buildResponse(true, Status::HTTP_NO_CONTENT),
            Status::HTTP_NO_CONTENT
        );
    }

    public function error(?string $message = null, mixed $data = null, int $status = Status::HTTP_BAD_REQUEST): JsonResponse
    {
        return response()->json(
            $this->buildResponse(false, $status, $message, $data),
            $status
        );
    }

    public function unauthorized(?string $message = null): JsonResponse
    {
        return response()->json(
            $this->buildResponse(false, Status::HTTP_UNAUTHORIZED, $message ?? __('lang.unauthorized')),
            Status::HTTP_UNAUTHORIZED
        );
    }

    public function forbidden(?string $message = null): JsonResponse
    {
        return response()->json(
            $this->buildResponse(false, Status::HTTP_FORBIDDEN, $message ?? __('lang.forbidden')),
            Status::HTTP_FORBIDDEN
        );
    }

    public function notFound(?string $message = null): JsonResponse
    {
        return response()->json(
            $this->buildResponse(false, Status::HTTP_NOT_FOUND, $message ?? __('lang.not_found')),
            Status::HTTP_NOT_FOUND
        );
    }

    public function internalError(?string $message = null): JsonResponse
    {
        return response()->json(
            $this->buildResponse(false, Status::HTTP_INTERNAL_SERVER_ERROR, $message ?? __('lang.server_error')),
            Status::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
