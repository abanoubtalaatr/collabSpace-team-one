<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SearchResultResource;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $globalSearchService): JsonResponse
    {
        $rawQuery = $request->query('q', '');
        $query = is_scalar($rawQuery) ? trim((string) $rawQuery) : '';

        $rawType = $request->query('type', '');
        $type = is_scalar($rawType) ? strtolower(trim((string) $rawType)) : '';

        $rawField = $request->query('field', '');
        $field = is_scalar($rawField) ? strtolower(trim((string) $rawField)) : '';

        $results = $globalSearchService->search($query, $type, $field);
        $resolvedResults = SearchResultResource::collection($results)->resolve($request);

        return response()->json([
            'query' => $query,
            'filters' => [
                'type' => $type ?: null,
                'field' => $field ?: null,
            ],
            'groups' => collect($resolvedResults)
                ->groupBy('type')
                ->map(fn ($items, string $type): array => [
                    'type' => $type,
                    'count' => $items->count(),
                    'results' => $items->values()->all(),
                ])
                ->values()
                ->all(),
        ]);
    }
}
