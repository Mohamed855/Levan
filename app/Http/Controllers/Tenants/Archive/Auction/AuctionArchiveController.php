<?php

namespace App\Http\Controllers\Tenants\Archive\Auction;

use App\Actions\Tenants\Auctions\ForceDeleteAuctionAction;
use App\Actions\Tenants\Auctions\RestoreAuctionAction;
use App\DataTables\Tenants\Auctions\AuctionArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Models\Tenants\Auctions\Auction;

class AuctionArchiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auctions_archive.view')->only(['index']);
        $this->middleware('can:auctions_archive.restore')->only(['restore']);
        $this->middleware('can:auctions_archive.force_delete')->only(['forceDelete']);
    }

    public function index(AuctionArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenant.archive.auctions.index');
    }

    public function restore(ArchiveItemIdRequest $request, RestoreAuctionAction $action)
    {
        $auction = Auction::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('restore', $auction);

        try {
            $action->execute($auction);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استعادة المزاد بنجاح']);
            }

            return redirect()->back()->with('success', 'تم استعادة المزاد بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استعادة المزاد'], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة المزاد');
        }
    }

    public function forceDelete(ArchiveItemIdRequest $request, ForceDeleteAuctionAction $action)
    {
        $auction = Auction::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('forceDelete', $auction);

        try {
            $action->execute($auction);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف المزاد نهائياً بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف المزاد نهائياً بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف المزاد نهائياً',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف المزاد نهائياً');
        }
    }
}
