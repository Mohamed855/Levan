<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Archive\Auctions;

use App\Actions\Tenant\Archive\ManageTenantArchiveAction;
use App\DataTables\Tenant\Archive\Auctions\AuctionArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Auction;
use Illuminate\Http\RedirectResponse;

final class AuctionArchiveController extends Controller
{
    public function __construct(private readonly ManageTenantArchiveAction $archive)
    {
        $this->middleware('can:archives.view')->only('index');
        $this->middleware('can:archives.restore')->only('restore');
        $this->middleware('can:archives.force_delete')->only('forceDelete');
    }

    public function index(AuctionArchiveDataTable $dataTable) { return $dataTable->render('tenant.archives.auctions.index'); }

    public function restore(int $id): RedirectResponse
    {
        $this->archive->restore(Auction::onlyTrashed()->findOrFail($id));
        return back()->with('success', 'تم استرجاع المزاد بنجاح.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $auction = Auction::onlyTrashed()->findOrFail($id);
        $this->archive->forceDelete($auction);
        return back()->with('success', 'تم حذف المزاد نهائيًا.');
    }
}
