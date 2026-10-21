<section class="card"><div class="card-body"><div class="row g-3"><x-shared.form.controls.input name="display_name" label="اسم الدور" :value="old('display_name', $role->display_name)" required /><x-shared.form.controls.input name="name" label="المفتاح الإنجليزي" :value="old('name', $role->name)" required /><div class="col-12"><label class="form-label">الصلاحيات</label><div class="row g-2">@foreach($permissions as $permission)<div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permission_ids', $role->permissions->pluck('id')->all()), true))><span class="form-check-label">{{ $permission->display_name }}</span></label></div>@endforeach</div></div></div></div></section><div class="mt-4 d-flex gap-2">
    <a class="btn btn-label-secondary" href="{{ route('tenant.roles.index') }}">إلغاء</a>
    @if (! $role->exists)
        <x-form.wizard.draft-button />
    @endif
    <button type="submit" class="btn btn-primary">حفظ الدور</button>
</div>
