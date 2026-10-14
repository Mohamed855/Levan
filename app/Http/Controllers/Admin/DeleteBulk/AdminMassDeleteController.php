<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\DeleteBulk;

use App\Actions\Admin\MassDelete\DeleteAdminRecordsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteBulk\MassDeleteRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\ContentManagement\Client;
use App\Models\Admin\ContentManagement\Portfolio;
use App\Models\Admin\Coupons\Coupon;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Tickets\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AdminMassDeleteController extends Controller
{
    /**
     * Whitelist of allowed central admin models with their permission check.
     *
     * @var array<string, array{class: class-string, permission: string|null}>
     */
    public const ALLOWED_MODELS = [
        'Plan' => [
            'class' => Plan::class,
            'permission' => 'plans.delete',
        ],
        'Coupon' => [
            'class' => Coupon::class,
            'permission' => 'coupons.delete',
        ],
        'Tenant' => [
            'class' => Tenant::class,
            'permission' => 'companies.delete',
        ],
        'Subscription' => [
            'class' => Subscription::class,
            'permission' => 'subscriptions.delete',
        ],
        'Bill' => [
            'class' => Bill::class,
            'permission' => 'bills.delete',
        ],
        'Ticket' => [
            'class' => Ticket::class,
            'permission' => 'tickets.delete',
        ],
        'AdminUser' => [
            'class' => AdminUser::class,
            'permission' => 'users.delete',
        ],
        'Client' => [
            'class' => Client::class,
            'permission' => 'clients.delete',
        ],
        'Portfolio' => [
            'class' => Portfolio::class,
            'permission' => 'clients.delete',
        ],
    ];

    public function __construct(private readonly DeleteAdminRecordsAction $deleteRecords)
    {
        $this->middleware('auth:admin');
    }

    public function massDelete(MassDeleteRequest $request): JsonResponse
    {
        $modelKey = (string) $request->validated('model');
        $idsData = $request->validated('ids');

        $config = self::ALLOWED_MODELS[$modelKey];
        $permission = $config['permission'];

        if ($permission && ! Auth::guard('admin')->user()?->can($permission)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتنفيذ عملية الحذف هذه.'], 403);
        }

        $ids = $this->parseIds($idsData);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'لا توجد معرفات صحيحة.'], 400);
        }

        $modelClass = $config['class'];
        if (! class_exists($modelClass)) {
            return response()->json(['success' => false, 'message' => 'فئة النموذج غير موجودة.'], 400);
        }

        $records = $modelClass::query()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            Gate::forUser(Auth::guard('admin')->user())->authorize('delete', $record);
        }

        try {
            $deleted = $this->deleteRecords->execute($records);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deleted} عنصر بنجاح.",
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء تنفيذ الحذف.'], 500);
        }
    }

    private function parseIds(mixed $idsData): array
    {
        if (is_string($idsData)) {
            $ids = explode(',', $idsData);
        } elseif (is_array($idsData)) {
            $ids = $idsData;
        } else {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));
    }
}
