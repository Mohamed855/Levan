<?php

namespace App\Http\Controllers\Tenants\Archive\Auction;

use App\DataTables\Tenants\Auctions\AuctionArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\AuctionService;

class AuctionArchiveController extends Controller
{
    public function __construct(private readonly AuctionService $auctionService)
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auctions_archive.view')->only(['index']);
        $this->middleware('can:auctions_archive.restore')->only(['restore']);
        $this->middleware('can:auctions_archive.force_delete')->only(['forceDelete']);
    }

    public function index(AuctionArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenants.archive.auctions.index');
    }

    public function restore(int $id)
    {
        try {
            $auction = Auction::onlyTrashed()->findOrFail($id);
            $auction->restore();

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

    public function forceDelete(int $id)
    {
        try {
            $auction = Auction::onlyTrashed()->findOrFail($id);
            $this->auctionService->forceDeleteAuction($auction);

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
