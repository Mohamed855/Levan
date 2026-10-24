<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Archive\Users;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\User;

final class UserArchiveDataTable extends AbstractDataTable
{
    protected function getTableId(): string { return 'users-archive-table'; }
    protected function resource() { return User::onlyTrashed(); }
    protected function getColumns(): array { return [
        ['data' => 'name', 'name' => 'name', 'title' => 'الاسم'],
        ['data' => 'email', 'name' => 'email', 'title' => 'البريد الإلكتروني'],
        ['data' => 'phone', 'name' => 'phone', 'title' => 'الجوال'],
        ['data' => 'deleted_at', 'name' => 'deleted_at', 'title' => 'تاريخ الحذف'],
        ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
    ]; }
    protected function addCustomColumns($datatable): void { $datatable
        ->editColumn('name', fn (User $user): string => '<span class="fw-semibold">'.e($user->name).'</span>')
        ->editColumn('email', fn (User $user): string => e($user->email))
        ->editColumn('phone', fn (User $user): string => e($user->phone ?? '-'))
        ->editColumn('deleted_at', fn (User $user): string => $user->deleted_at?->format('Y-m-d H:i') ?? '-')
        ->addColumn('actions', fn (User $user): string => view('tenant.archives.users.action', ['id' => $user->id])->render()); }
    protected function rawColumns(): array { return ['name', 'actions']; }
    protected function getSearchableColumns(): array { return ['name', 'email', 'phone']; }
    protected function getCustomOrder(): array { return [3, 'desc']; }
    protected function buttons(): array { return [[ 'extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']] ]]; }
}
