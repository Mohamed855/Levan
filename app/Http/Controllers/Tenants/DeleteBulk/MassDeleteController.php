<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\DeleteBulk;

use App\Actions\Tenants\MassDelete\DeleteTenantRecordsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\DeleteBulk\MassDeleteRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use LogicException;

class MassDeleteController extends Controller
{
    public function __construct(private readonly DeleteTenantRecordsAction $deleteRecords) {}

    /*
    |--------------------------------------------------------------------------
    | Mass Delete Handler
    |--------------------------------------------------------------------------
    */
    public function massDelete(MassDeleteRequest $request)
    {
        $model = (string) $request->validated('model');
        $ids = array_map('intval', $request->validated('ids'));
        $modelClass = config('tenant-mass-delete')[$model]['class'];

        return $this->processSmallDelete($request, $modelClass, $ids);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Small Delete
    |--------------------------------------------------------------------------
    */
    /** @param class-string<Model> $modelClass */
    private function processSmallDelete(MassDeleteRequest $request, string $modelClass, array $ids)
    {
        $records = $modelClass::query()->whereIn('id', $ids)->get();

        if ($records->count() !== count($ids)) {
            throw ValidationException::withMessages(['ids' => 'أحد السجلات المحددة غير متاح للحذف. لم يتم حذف أي سجل.']);
        }

        foreach ($records as $record) {
            Gate::forUser($request->user('tenant'))->authorize('delete', $record);
        }

        try {
            $deleted = $this->deleteRecords->execute($records);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deleted} عنصر بنجاح.",
            ]);
        } catch (ValidationException|ModelNotFoundException $e) {
            throw $e;
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف.'], 500);
        }
    }
}
