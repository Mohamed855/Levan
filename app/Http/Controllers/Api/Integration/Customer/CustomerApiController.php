<?php

namespace App\Http\Controllers\Api\Integration\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Integration\CustomerResource;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->with([
            'company',
            'portfolio',
            'relationshipManager',
            'customerStatus',
            'nationalityCountry',
        ]);

        if ($request->filled('customer_status_id')) {
            $query->where('customer_status_id', $request->customer_status_id);
        }

        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('portfolio_id')) {
            $query->where('portfolio_id', $request->portfolio_id);
        }

        if ($request->filled('relationship_manager_id')) {
            $query->where('relationship_manager_id', $request->relationship_manager_id);
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('identity_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        $customers = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::with([
            'company',
            'portfolio',
            'relationshipManager',
            'customerStatus',
            'nationalityCountry',
        ])->find($id);

        if (! $customer) {
            return response()->json([
                'success' => false,
                'message' => 'العميل غير موجود',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }
}
