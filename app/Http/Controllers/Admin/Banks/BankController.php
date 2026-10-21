<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Banks;

use App\Actions\Admin\Banks\SaveBankAction;
use App\DataTables\Admin\Banks\BankDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banks\StoreBankRequest;
use App\Http\Requests\Admin\Banks\UpdateBankRequest;
use App\Models\Admin\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:banks.view')->only('index');
        $this->middleware('can:banks.create')->only(['create', 'store']);
        $this->middleware('can:banks.update')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(BankDataTable $dataTable)
    {
        $statistics = [
            'total'               => Bank::query()->count(),
            'active'              => Bank::query()->where('is_active', true)->count(),
            'inactive'            => Bank::query()->where('is_active', false)->count(),
            'with_account_number' => Bank::query()->whereNotNull('account_number')->count(),
            'with_logo'           => Bank::query()->whereNotNull('logo_path')->count(),
        ];

        $filterOptions = [
            'is_active' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'معطل'],
            ],
        ];

        return $dataTable->render('admin.banks.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('admin.banks.create', ['bank' => new Bank()]);
    }

    public function store(StoreBankRequest $request, SaveBankAction $action): RedirectResponse
    {
        $action->create($request->validated(), $request->file('logo'));

        return to_route('admin.banks.index')->with('success', 'تم إضافة الحساب البنكي بنجاح.');
    }

    public function edit(Bank $bank): View
    {
        return view('admin.banks.edit', compact('bank'));
    }

    public function update(UpdateBankRequest $request, Bank $bank, SaveBankAction $action): RedirectResponse
    {
        $action->update($bank, $request->validated(), $request->file('logo'));

        return to_route('admin.banks.index')->with('success', 'تم تحديث بيانات الحساب البنكي بنجاح.');
    }

    public function toggleStatus(Request $request, Bank $bank, SaveBankAction $action): JsonResponse|RedirectResponse
    {
        $status = $action->toggleStatus($bank);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'message'   => $status ? 'تم تفعيل الحساب البنكي بنجاح.' : 'تم تعطيل الحساب البنكي بنجاح.',
                'is_active' => $status,
            ]);
        }

        return back()->with('success', 'تم تحديث حالة الحساب البنكي بنجاح.');
    }

    public function destroy(Request $request, Bank $bank): JsonResponse|RedirectResponse
    {
        $bank->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الحساب البنكي بنجاح.']);
        }

        return to_route('admin.banks.index')->with('success', 'تم حذف الحساب البنكي بنجاح.');
    }
}
