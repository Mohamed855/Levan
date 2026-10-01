<?php

namespace App\Http\Controllers\Tenants\Archive\Collection;

use App\Http\Controllers\Controller;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Services\Tenants\Archive\Collection\CollectionArchiveService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class CollectionArchiveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    | Initialize controller with required dependencies and middleware.
    */

    protected CollectionArchiveService $collectionArchiveService;

    public function __construct(CollectionArchiveService $collectionArchiveService)
    {
        $this->collectionArchiveService = $collectionArchiveService;

        $this->middleware('can:collections_archive.view')->only(['index']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Archived Collections List
    |--------------------------------------------------------------------------
    | Display a listing of archived collections with DataTables integration.
    */
    public function index(Request $request): View|JsonResponse
    {
        // معالجة الاستعادة الجماعية
        if ($request->has('mass_restore') && $request->ajax()) {
            try {
                $count = $this->collectionArchiveService->massRestoreCollections($request->ids);
                return response()->json([
                    'success' => true,
                    'message' => "تم استرجاع {$count} تحصيل بنجاح"
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء الاستعادة الجماعية'
                ], 500);
            }
        }

        // معالجة الحذف النهائي الجماعي
        if ($request->has('mass_force_delete') && $request->ajax()) {
            try {
                $count = $this->collectionArchiveService->massForceDeleteCollections($request->ids);
                return response()->json([
                    'success' => true,
                    'message' => "تم حذف {$count} تحصيل نهائياً"
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء الحذف النهائي الجماعي'
                ], 500);
            }
        }

        if ($request->ajax()) {
            $query = $this->collectionArchiveService->getArchivedCollectionsQuery();

            // Apply filters
            $filters = $request->only([
                'status',
                'customer_id',
                'payment_method_id',
                'collection_type',
                'payment_type',
                'date_from',
                'date_to',
                'added_by'
            ]);
            $query = $this->collectionArchiveService->filterArchivedCollectionsQuery($query, $filters);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('customer_info', function (Collection $collection) {
                    return '<div>' .
                        '<div class="fw-medium">' . e($collection->customer->name) . '</div>' .
                        '<small class="text-muted">' . e($collection->customer->phone) . '</small>' .
                        '</div>';
                })
                ->addColumn('amount_info', function (Collection $collection) {
                    return '<div>' .
                        '<div class="fw-medium text-primary">' . $collection->formatted_collected_amount . '</div>' .
                        '<small class="text-muted">رقم السند: ' . e($collection->receipt_number) . '</small>' .
                        '</div>';
                })
                ->addColumn('collection_details', function (Collection $collection) {
                    $typeClass = $collection->collection_type === 'cash' ? 'bg-label-success' : 'bg-label-info';
                    $paymentTypeClass = $collection->payment_type === 'settlement' ? 'bg-label-primary' : 'bg-label-secondary';

                    return '<div>' .
                        '<span class="badge ' . $typeClass . ' mb-1">' . $collection->collection_type_label . '</span><br>' .
                        '<span class="badge ' . $paymentTypeClass . '">' . $collection->payment_type_label . '</span>' .
                        '</div>';
                })
                ->addColumn('payment_method', function (Collection $collection) {
                    return '<span class="text-primary">' . e($collection->paymentMethod->name) . '</span>';
                })
                ->addColumn('collection_date_formatted', function (Collection $collection) {
                    return '<span class="text-success">' . $collection->formatted_collection_date . '</span>';
                })
                ->addColumn('status', function (Collection $collection) {
                    return '<span class="badge ' . $collection->status_class . '">' . $collection->status_label . '</span>';
                })
                ->addColumn('added_by', function (Collection $collection) {
                    return $collection->addedBy ? e($collection->addedBy->name) : 'غير محدد';
                })
                ->addColumn('deleted_at_formatted', function (Collection $collection) {
                    return $collection->deleted_at->locale('ar')->translatedFormat('d F Y');
                })
                ->addColumn('attachment', function (Collection $collection) {
                    if ($collection->attachment) {
                        $url = asset('storage/' . $collection->attachment);
                        return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-icon btn-text-primary" title="عرض المرفق">
                                    <i class="ti ti-paperclip"></i>
                                </a>';
                    }
                    return '<span class="text-muted">لا يوجد</span>';
                })
                ->addColumn('action', function ($row) {
                    $restoreUrl = tenant_route_name('archive.collections.restore', $row->id);
                    $forceDeleteUrl = tenant_route_name('archive.collections.force-delete', $row->id);
                    $restoreFormId = 'restore-form-' . $row->id;
                    $deleteFormId = 'force-delete-form-' . $row->id;

                    return '<div class="d-flex justify-content-center">
                                <button title="استرجاع التحصيل" type="button" onclick="confirmRestore(' . $row->id . ')" class="btn btn-sm btn-icon btn-text-success me-1">
                                    <i class="ti ti-refresh"></i>
                                </button>
                                <button title="حذف نهائي" type="button" onclick="confirmForceDelete(' . $row->id . ')" class="btn btn-sm btn-icon btn-text-danger">
                                    <i class="ti ti-trash-x"></i>
                                </button>
                                <form id="' . $restoreFormId . '" action="' . $restoreUrl . '" method="POST" style="display: none;">
                                    ' . csrf_field() . '
                                    ' . method_field('PATCH') . '
                                </form>
                                <form id="' . $deleteFormId . '" action="' . $forceDeleteUrl . '" method="POST" style="display: none;">
                                    ' . csrf_field() . '
                                    ' . method_field('DELETE') . '
                                </form>
                            </div>';
                })
                ->orderColumn('customer_info', fn($query, $order) => $query->orderBy('customer_id', $order))
                ->orderColumn('amount_info', fn($query, $order) => $query->orderBy('collected_amount', $order))
                ->orderColumn('collection_date_formatted', fn($query, $order) => $query->orderBy('collection_date', $order))
                ->orderColumn('status', fn($query, $order) => $query->orderBy('status', $order))
                ->orderColumn('deleted_at_formatted', fn($query, $order) => $query->orderBy('deleted_at', $order))
                ->filterColumn('customer_info', function ($query, $keyword) {
                    $query->whereHas('customer', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('phone', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('receipt_number', fn($query, $keyword) => $query->where('receipt_number', 'like', "%{$keyword}%"))
                ->rawColumns(['checkbox', 'customer_info', 'amount_info', 'collection_details', 'payment_method', 'collection_date_formatted', 'status', 'attachment', 'action'])
                ->make(true);
        }

        $statistics = $this->collectionArchiveService->getArchivedCollectionStatistics();
        $formData = $this->collectionArchiveService->getFormData();

        return view('tenants.archive.collections.index', [
            'totalArchivedCollections' => $statistics['total'],
            'archivedPendingCollections' => $statistics['pending'],
            'archivedApprovedCollections' => $statistics['approved'],
            'archivedRejectedCollections' => $statistics['rejected'],
            'totalArchivedAmount' => number_format($statistics['total_amount'], 2),
            'archivedCashCollections' => $statistics['cash_collections'],
            'archivedScheduledCollections' => $statistics['scheduled_collections'],
            'recentlyArchivedCollections' => $statistics['recently_archived'],
            'customers' => $formData['customers'],
            'paymentMethods' => $formData['paymentMethods'],
            'users' => $formData['users'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Collection
    |--------------------------------------------------------------------------
    | Restore the specified archived collection.
    */
    public function restore(int $collectionId): RedirectResponse
    {
        try {
            $collection = Collection::onlyTrashed()->findOrFail($collectionId);
            $this->collectionArchiveService->restoreCollection($collection);

            return redirect()
                ->back()
                ->with('success', 'تم استرجاع التحصيل بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء استرجاع التحصيل');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Force Delete Collection
    |--------------------------------------------------------------------------
    | Permanently delete the specified archived collection.
    */
    public function forceDelete(int $collectionId): RedirectResponse
    {
        try {
            $collection = Collection::onlyTrashed()->findOrFail($collectionId);
            $this->collectionArchiveService->forceDeleteCollection($collection);

            return redirect()
                ->back()
                ->with('success', 'تم حذف التحصيل نهائياً');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الحذف النهائي');
        }
    }
}
