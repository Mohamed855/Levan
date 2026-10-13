<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Export;

use App\Http\Controllers\Controller;
use App\Services\Tenants\BusinessCenter\Customer\Export\CustomerExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerExportController extends Controller
{
    public function __construct(private CustomerExportService $service)
    {
        $this->middleware('can:customers.export');
    }

    public function exportCustomers(Request $request): JsonResponse
    {
        try {
            $customerIds = $this->extractCustomerIds($request);

            if (empty($customerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى تحديد عميل واحد على الأقل للتصدير',
                ], 422);
            }

            $selectedFields = (array) $request->input('selected_fields', []);
            $truncateBy = $request->input('truncate_by');

            $result = $this->service->exportCustomers($customerIds, $selectedFields, $truncateBy);

            return response()->json($result);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تصدير البيانات',
            ], 500);
        }
    }

    // Check Export Progress
    public function checkExportProgress(Request $request): JsonResponse
    {
        $exportId = $request->input('export_id');

        if (! $exportId) {
            Log::error($exportId);

            return response()->json([
                'success' => false,
                'message' => 'معرف التصدير مطلوب',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $this->service->getExportProgress($exportId),
        ]);
    }

    // Download Export File
    public function downloadExportFile(string $file): BinaryFileResponse|RedirectResponse
    {
        $disk = Storage::disk('tenant_public');
        $relative = 'exports/'.basename($file);

        if (! $disk->exists($relative)) {
            return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
        }

        return response()->download($disk->path($relative))->deleteFileAfterSend(true);
    }

    /*
    |============================================================================
    |============================================================================
    |                            Private Methods
    |============================================================================
    |============================================================================
    */
    // يُرسل الطرف الأمامي المعرفات كنص JSON في حقل واحد لتفادي حد
    // max_input_vars الذي كان يقصّ التحديد عند إرسالها كمصفوفة حقول.
    private function extractCustomerIds(Request $request): array
    {
        $raw = $request->input('customer_ids', []);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }
}
