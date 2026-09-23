<?php

namespace App\Http\Controllers\Api\Integration\Collection;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Integration\CollectionResource;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Collection::query()->with(['customer', 'paymentMethod', 'addedBy', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        if ($request->filled('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('collection_date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->whereDate('collection_date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->whereDate('collection_date', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $collections = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => CollectionResource::collection($collections),
            'meta'    => [
                'current_page' => $collections->currentPage(),
                'last_page'    => $collections->lastPage(),
                'per_page'     => $collections->perPage(),
                'total'        => $collections->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $collection = Collection::with(['customer', 'paymentMethod', 'addedBy', 'approvedBy'])->find($id);

        if (!$collection) {
            return response()->json([
                'success' => false,
                'message' => 'التحصيل غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new CollectionResource($collection),
        ]);
    }
}
