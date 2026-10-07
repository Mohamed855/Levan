<?php

namespace App\Http\Controllers\Tenants\DeleteBulk;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\DeleteBulk\MassDeleteRequest;

class MassDeleteController extends Controller
{
    /** @var array<string, string> */
    private const MODEL_DELETE_PERMISSIONS = [
        'Assignment' => 'assignments.delete',
        'Property' => 'properties.delete',
        'Auction' => 'auctions.delete',
    ];

    /*
    |--------------------------------------------------------------------------
    | Mass Delete Handler
    |--------------------------------------------------------------------------
    */
    public function massDelete(MassDeleteRequest $request)
    {
        $model = (string) $request->validated('model');
        $idsData = $request->validated('ids');

        $permission = self::MODEL_DELETE_PERMISSIONS[$model] ?? null;
        if ($permission && ! $request->user('tenant')?->can($permission)) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتنفيذ عملية الحذف هذه.'], 403);
        }

        $ids = $this->parseIds($idsData);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'لا توجد معرفات صحيحة.'], 400);
        }

        $modelClass = config("models.$model");
        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['success' => false, 'message' => 'الموديل غير موجود.'], 400);
        }

        return $this->processSmallDelete($modelClass, $ids);
    }

    /*
    |--------------------------------------------------------------------------
    | Process Small Delete
    |--------------------------------------------------------------------------
    */
    private function processSmallDelete($modelClass, $ids)
    {
        try {
            $deleted = $modelClass::whereIn('id', $ids)->delete();
            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deleted} عنصر بنجاح."
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحذف.'], 500);
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

        return array_values(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0));
    }
}
