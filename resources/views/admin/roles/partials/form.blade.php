<section class="card">
    <div class="card-body">
        <div class="row g-3">
            <x-shared.form.controls.input name="display_name" label="اسم الدور" :value="old('display_name', $role->display_name)" required />
            <x-shared.form.controls.input name="name" label="المفتاح الإنجليزي" :value="old('name', $role->name)" required />
            <div class="col-12">
                <label class="form-label">الصلاحيات</label>
                @foreach ($permissionGroups as $group => $groupPermissions)
                    <section class="border rounded p-3 mb-3">
                        <h6 class="mb-3">{{ $group }}</h6>
                        <div class="row g-2">
                            @foreach ($groupPermissions as $permission)
                                <div class="col-md-4">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $role->permissions->pluck('id')->all()), true))>
                                        <span class="form-check-label">{{ \App\Support\Access\AdminPermissionCatalog::label($permission) }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</section>
<div class="mt-4 d-flex gap-2"><a class="btn btn-label-secondary" href="{{ route('admin.roles.index') }}">العودة</a><button class="btn btn-primary">حفظ الدور</button></div>
