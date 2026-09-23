<?php

namespace App\Http\Controllers\Tenants\BusinessCenter\Customer\Import;

use App\Http\Controllers\Controller;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GetCustomerImportController extends Controller
{
    public function getPortfoliosByCompany(Request $request): JsonResponse
    {
        $request->validate(['company_id' => 'required|exists:companies,id']);

        $portfolios = Portfolio::where('company_id', $request->company_id)->where('status', 'active')->select('id', 'name')->orderBy('name')->get();

        return response()->json(['success' => true, 'portfolios' => $portfolios]);
    }

    // في Controller الخاص بك (CustomerImportController)

    public function getImportMetadata()
    {
        $nationalities  = SettingsCountry::where('status', 'active')->select('id', 'name')->orderBy('id')->get();
        $statuses       = SettingsCustomerStatus::where('status', 'active')->select('id', 'name')->orderBy('id')->get();
        $managers       = User::where('status', 'active')->select('id', 'name', 'employee_number')->orderBy('id')->get();

        return response()->json([
            'nationalities' => $nationalities,
            'statuses' => $statuses,
            'managers' => $managers
        ]);
    }
}
