<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Settings\Bank;

use App\Actions\Admin\Settings\ManageBankAction;
use App\DataTables\Admin\Setting\Bank\BankDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Settings\Bank\StoreBankRequest;
use App\Http\Requests\Admin\Settings\Bank\UpdateBankRequest;
use App\Models\Admin\Bank\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SettingsBankController extends Controller
{
    private string $page = 'admin.settings.banks';

    private string $route = 'admin.settings.banks';

    public function __construct(private readonly ManageBankAction $banks)
    {
        $this->authorizeResource(Bank::class, 'bank');
        $this->middleware('can:bank_settings.view');
    }

    public function index(BankDataTable $dataTable): mixed
    {
        $filterOptions = ['statuses' => [
            ['value' => 'active', 'label' => 'نشط'],
            ['value' => 'inactive', 'label' => 'غير نشط'],
        ]];

        return $dataTable->render($this->page.'.index', compact('filterOptions'));
    }

    public function create(): View
    {
        return view($this->page.'.create');
    }

    public function store(StoreBankRequest $request): RedirectResponse
    {
        $this->banks->create($request->validated(), $request->file('icon'));

        return redirect()->route($this->route.'.index')->with('success', 'تم إضافة البنك بنجاح');
    }

    public function edit(Bank $bank): View
    {
        return view($this->page.'.edit', compact('bank'));
    }

    public function update(UpdateBankRequest $request, Bank $bank): RedirectResponse
    {
        $this->banks->update($bank, $request->validated(), $request->file('icon'));

        return redirect()->route($this->route.'.index')->with('success', 'تم تحديث البنك بنجاح');
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Bank $bank): JsonResponse
    {
        $status = $this->banks->toggleStatus($bank);

        return response()->json([
            'success' => true,
            'message' => $status ? 'تم تفعيل البنك بنجاح' : 'تم تعطيل البنك بنجاح',
            'status' => $status,
        ]);
    }

    public function destroy(AdminAuthenticatedRequest $request, Bank $bank): RedirectResponse|JsonResponse
    {
        $this->banks->delete($bank);
        $message = 'تم حذف البنك بنجاح';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route($this->route.'.index')->with('success', $message);
    }
}
