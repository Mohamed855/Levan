<?php

namespace App\Http\Controllers\Tenants\License;

use App\Http\Controllers\Controller;
use App\Services\Tenants\License\LicenseService;
use DomainException;
use Exception;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $licenseService) {}

    public function assign(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        try {
            $result = $this->licenseService->assignLicenseToUser($request->user_id);

            return response()->json($result);
            //
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تخصيص الترخيص',
            ], 500);
        }
    }

    public function release(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        try {
            $result = $this->licenseService->releaseLicenseFromUser($request->user_id);

            return response()->json($result);
            //
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحرير الترخيص',
            ], 500);
        }
    }
}
