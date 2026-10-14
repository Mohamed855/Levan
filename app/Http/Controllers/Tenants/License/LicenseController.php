<?php

namespace App\Http\Controllers\Tenants\License;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\License\ManageLicenseRequest;
use App\Services\Tenants\License\LicenseService;
use DomainException;
use Exception;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $licenseService) {}

    public function assign(ManageLicenseRequest $request)
    {
        try {
            $result = $this->licenseService->assignLicenseToUser($request->validated('user_id'));

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

    public function release(ManageLicenseRequest $request)
    {
        try {
            $result = $this->licenseService->releaseLicenseFromUser($request->validated('user_id'));

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
