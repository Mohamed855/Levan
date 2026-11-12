<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Services\Shared\Navigation\DashboardSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardSearchController extends Controller
{
    /**
     * Return JSON list of searchable dashboard pages.
     */
    public function __invoke(Request $request, DashboardSearchService $service): JsonResponse
    {
        $scope = $request->route()?->defaults['dashboard_scope'] ?? $request->query('scope');

        $pages = $service->getSearchablePages(is_string($scope) ? $scope : null);

        return response()->json([
            'pages' => $pages,
        ]);
    }
}
