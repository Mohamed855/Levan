<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\DeleteBulk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DeleteBulk\TenantMassDeleteRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class TenantMassDeleteController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'Assignment' => Assignment::class,
        'Auction' => Auction::class,
        'Property' => Property::class,
    ];

    public function __invoke(TenantMassDeleteRequest $request): JsonResponse
    {
        $user = $request->user('tenant');
        if (! $user || ! Hash::check((string) $request->validated('password'), (string) $user->password)) {
            return response()->json(['success' => false, 'message' => 'كلمة المرور غير صحيحة.'], 401);
        }

        $ids = array_values(array_unique(array_map('intval', $request->validated('ids'))));
        $modelClass = self::MODELS[$request->validated('model')];
        $records = $modelClass::query()->whereKey($ids)->get();

        if ($records->count() !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'تتضمن العناصر المحددة سجلات غير متاحة.']);
        }

        foreach ($records as $record) {
            Gate::forUser($user)->authorize('delete', $record);
        }

        DB::transaction(function () use ($records): void {
            $this->assertCanDelete($records);
            $records->each(static fn (Model $record) => $record->delete());
        });

        return response()->json([
            'success' => true,
            'message' => 'تم نقل '.$records->count().' عنصر إلى سلة المهملات بنجاح.',
        ]);
    }

    /** @param Collection<int, Model> $records */
    private function assertCanDelete(Collection $records): void
    {
        if ($records->first() instanceof Assignment && $records->contains(
            static fn (Assignment $assignment): bool => $assignment->properties()->exists() || $assignment->auctions()->exists(),
        )) {
            throw ValidationException::withMessages(['ids' => 'لا يمكن حذف إسناد مرتبط بعقارات أو مزادات.']);
        }

        if ($records->first() instanceof Property && $records->contains(
            static fn (Property $property): bool => $property->auctions()->whereNull('auctions.deleted_at')->exists(),
        )) {
            throw ValidationException::withMessages(['ids' => 'لا يمكن حذف عقار مرتبط بمزاد نشط.']);
        }
    }
}
