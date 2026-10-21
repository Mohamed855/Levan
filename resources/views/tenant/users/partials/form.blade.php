<section class="card"><div class="card-body"><div class="row g-3">
    <x-shared.form.controls.input name="name" label="الاسم" :value="old('name', $user->name)" required />
    <x-shared.form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="old('email', $user->email)" required />
    <x-shared.form.controls.input name="phone" type="tel" label="رقم الجوال" :value="old('phone', $user->phone)" />
    <x-shared.form.controls.select name="status" label="الحالة" :options="['active' => 'نشط', 'inactive' => 'غير نشط']" :value="old('status', $user->status ?: 'active')" required />
    <x-shared.form.controls.password name="password" :label="$user->exists ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور'" :required="! $user->exists" />
    <x-shared.form.controls.password name="password_confirmation" label="تأكيد كلمة المرور" :required="! $user->exists" />
    <div class="col-12"><label class="form-label">الأدوار</label><div class="row g-2">@foreach ($roles as $role)<div class="col-md-4"><label class="form-check"><input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $user->roles->pluck('id')->all()), true))><span class="form-check-label">{{ $role->display_name }}</span></label></div>@endforeach</div></div>
</div></div></section>
<div class="mt-4 d-flex gap-2">
    <a class="btn btn-label-secondary" href="{{ route('tenant.users.index') }}">إلغاء</a>
    @if (! $user->exists)
        <x-form.wizard.draft-button />
    @endif
    <button type="submit" class="btn btn-primary">{{ $user->exists ? 'حفظ التعديلات' : 'إنشاء المستخدم' }}</button>
</div>
