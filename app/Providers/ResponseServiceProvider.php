<?php

namespace App\Providers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response as Status;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $buildResponse = function (bool $success, int $status, ?string $message = null, $data = null) {
            $isPaginated = $data instanceof ResourceCollection
                && $data->resource instanceof LengthAwarePaginator;

            return Response::json([
                'success' => $success,
                'message' => $message,
                'data' => $data ?: null,
                'paginate' => $isPaginated ? [
                    'per_page' => $data->resource->perPage(),
                    'current_page' => $data->resource->currentPage(),
                    'last_page' => $data->resource->lastPage()
                ] : null
            ], $status);
        };

        // --- Success Macros ---

        Response::macro('success', function ($message = null, $data = null) use ($buildResponse): JsonResponse {
            return $buildResponse(true, Status::HTTP_OK, $message, $data);
        });

        Response::macro('created', function ($message = null, $data = null) use ($buildResponse): JsonResponse {
            return $buildResponse(true, Status::HTTP_CREATED, $message, $data);
        });

        Response::macro('noContent', function () use ($buildResponse): JsonResponse {
            return $buildResponse(true, Status::HTTP_NO_CONTENT, null, null);
        });

        // --- Error Macros ---

        Response::macro('error', function ($message = null, $data = null, $status = Status::HTTP_BAD_REQUEST) use ($buildResponse): JsonResponse {
            return $buildResponse(false, $status, $message, $data);
        });

        Response::macro('unauthorized', function () use ($buildResponse): JsonResponse {
            return $buildResponse(false, Status::HTTP_UNAUTHORIZED, __('lang.unauthorized'), null);
        });

        Response::macro('forbidden', function () use ($buildResponse): JsonResponse {
            return $buildResponse(false, Status::HTTP_FORBIDDEN, __('lang.forbidden'), null);
        });

        Response::macro('notFound', function () use ($buildResponse): JsonResponse {
            return $buildResponse(false, Status::HTTP_NOT_FOUND, __('lang.not_found'), null);
        });

        Response::macro('internalError', function () use ($buildResponse): JsonResponse {
            return $buildResponse(false, Status::HTTP_INTERNAL_SERVER_ERROR, __('lang.server_error'), null);
        });
    }
}
