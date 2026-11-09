<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\AuctionEstablishments;

use App\Actions\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequestAction;
use App\Actions\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequestAction;
use App\DataTables\Tenants\AuctionEstablishments\AuctionEstablishmentRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequest;
use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestManagementService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class AuctionEstablishmentRequestsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auction_establishment_requests.view')->only(['index', 'edit', 'attachment']);
    }

    public function index(AuctionEstablishmentRequestsDataTable $table, AuctionEstablishmentRequestManagementService $management)
    {
        return $table->render('tenant.auction-establishment-requests.index', ['statistics' => $management->statistics()]);
    }

    public function edit(Auction $auction, AuctionEstablishmentRequestData $schema)
    {
        abort_unless(auth('tenant')->user()->can($auction->establishmentRequest ? 'auction_establishment_requests.edit' : 'auction_establishment_requests.create'), 403);
        if ($auction->isClosed() || $auction->establishmentRequest?->isApproved()) {
            throw ValidationException::withMessages(['data' => 'لا يمكن تعديل طلب معتمد أو طلب مزاد مقفل.']);
        }

        return view('tenant.auction-establishment-requests.edit', ['auction' => $auction, 'schema' => $schema, 'data' => $schema->defaults($auction)]);
    }

    public function update(SaveAuctionEstablishmentRequest $request, Auction $auction, SaveAuctionEstablishmentRequestAction $action)
    {
        $action->execute($auction, $request->validated(), $request->user('tenant')->id);

        return redirect(tenant_route_name('auctions.show', $auction))->with('success', 'تم حفظ طلب إقامة المزاد وإرساله للمراجعة وطلب إنشاء النموذج.');
    }

    public function approve(ApproveAuctionEstablishmentRequest $request, Auction $auction, ApproveAuctionEstablishmentRequestAction $action)
    {
        $action->execute($auction, $request->validated('approval_number'), $request->user('tenant')->id);

        return redirect(tenant_route_name('auctions.show', $auction))->with('success', 'تم اعتماد طلب إقامة المزاد وحفظ رقم الموافقة.');
    }

    public function attachment(Auction $auction, string $type, int $property = 0)
    {
        abort_unless(array_key_exists($type, app(AuctionEstablishmentRequestData::class)->attachmentTypes()), 404);
        $file = $auction->establishmentRequest?->data['attachments'][$type][$property] ?? null;
        $prefix = 'auction-establishments/'.hash('sha256', (string) tenant('id')).'/'.$auction->id.'/';
        abort_unless(is_array($file) && is_string($file['path'] ?? null) && str_starts_with($file['path'], $prefix), 404);
        abort_unless(Storage::disk('tenant_private')->exists($file['path']), 404);

        return Storage::disk('tenant_private')->download($file['path'], basename($file['name']));
    }
}
