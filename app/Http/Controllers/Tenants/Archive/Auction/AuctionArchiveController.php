<?php

namespace App\Http\Controllers\Tenants\Archive\Auction;

use App\DataTables\Tenants\Auctions\AuctionArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\Auctions\Auction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

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
        return $dataTable->render('tenants.archive.auctions.index', [
            'totalArchived' => Auction::onlyTrashed()->count(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $auction = Auction::onlyTrashed()->findOrFail($id);
            $auction->restore();

            return redirect()->back()->with('success', 'تم استعادة المزاد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة المزاد');
        }
    }

    public function forceDelete(int $id): JsonResponse
    {
        try {
            $auction = Auction::onlyTrashed()->findOrFail($id);
            $auction->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المزاد نهائياً بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المزاد نهائياً',
            ], 500);
        }
    }
}
