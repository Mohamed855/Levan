<?php

namespace App\Http\Controllers\Tenants\DeleteBulk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\Tenants\DeleteBulk\ProcessMassDelete;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MassDeleteController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Mass Delete Handler
    |--------------------------------------------------------------------------
    */
    public function massDelete(Request $request)
    {
        $model = $request->input('model');
        $idsData = $request->input('ids');
        // $password = $request->input('password');

        if (!$model || !$idsData) {
            return response()->json(['success' => false, 'message' => 'بيانات غير صحيحة.'], 400);
        }

        // if (!$password) {
        //     return response()->json(['success' => false, 'message' => 'يجب إدخال كلمة المرور.'], 400);
        // }

        // $user = Auth::user();
        // if (!Hash::check($password, $user->password)) {
        //     return response()->json(['success' => false, 'message' => 'كلمة المرور غير صحيحة.'], 401);
        // }

        $ids = $this->parseIds($idsData);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'لا توجد معرفات صحيحة.'], 400);
        }

        $modelClass = config("models.$model");
        if (!$modelClass || !class_exists($modelClass)) {
            return response()->json(['success' => false, 'message' => 'الموديل غير موجود.'], 400);
        }

        // $totalIds = count($ids);

        // if ($totalIds > 1000) {
        //     return $this->processLargeDelete($modelClass, $ids, $totalIds);
        // }

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

    /*
    |--------------------------------------------------------------------------
    | Process Large Delete
    |--------------------------------------------------------------------------
    */
    private function processLargeDelete($modelClass, $ids, $totalIds)
    {
        $batchId = Str::uuid();
        $chunks = array_chunk($ids, 100);

        foreach ($chunks as $chunk) {
            ProcessMassDelete::dispatch($modelClass, $chunk, $batchId)
                ->onQueue('default')
                ->delay(now()->addSeconds(rand(1, 3)));
        }

        return response()->json([
            'success' => true,
            'is_queued' => true,
            'batch_id' => $batchId,
            'message' => "تم إرسال {$totalIds} عنصر للحذف في الخلفية. ستتم معالجتها خلال دقائق.",
            'total_batches' => count($chunks)
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Parse IDs
    |--------------------------------------------------------------------------
    */
    private function parseIds($idsData)
    {
        if (is_string($idsData)) {
            $ids = explode(',', $idsData);
        } else {
            $ids = $idsData;
        }

        return array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Check Status
    |--------------------------------------------------------------------------
    */
    public function checkStatus(Request $request)
    {
        $batchId = $request->input('batch_id');

        $pendingJobs = DB::table('jobs')
            ->where('payload', 'LIKE', "%{$batchId}%")
            ->count();

        return response()->json([
            'completed' => $pendingJobs === 0,
            'pending_jobs' => $pendingJobs
        ]);
    }
}
