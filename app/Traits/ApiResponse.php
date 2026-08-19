<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = '', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ];

        if (! empty($meta)) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully.'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => empty($errors) ? null : $errors,
        ], $status);
    }

    /** Wrap a paginator + resource into a uniform paginated response. */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        $collection = $resourceClass::collection($paginator);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $collection->response()->getData(true)['data'],
            'errors' => null,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'path' => $paginator->path(),
            ],
        ]);
    }
}
