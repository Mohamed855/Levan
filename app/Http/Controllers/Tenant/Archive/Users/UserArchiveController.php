<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Archive\Users;

use App\Actions\Tenant\Archive\ManageTenantArchiveAction;
use App\DataTables\Tenant\Archive\Users\UserArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;

final class UserArchiveController extends Controller
{
    public function __construct(private readonly ManageTenantArchiveAction $archive)
    {
        $this->middleware('can:archives.view')->only('index');
        $this->middleware('can:archives.restore')->only('restore');
        $this->middleware('can:archives.force_delete')->only('forceDelete');
    }

    public function index(UserArchiveDataTable $dataTable) { return $dataTable->render('tenant.archives.users.index'); }

    public function restore(int $id): RedirectResponse
    {
        $this->archive->restore(User::onlyTrashed()->findOrFail($id));
        return back()->with('success', 'تم استرجاع المستخدم بنجاح.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $this->archive->forceDelete(User::onlyTrashed()->findOrFail($id));
        return back()->with('success', 'تم حذف المستخدم نهائيًا.');
    }
}
