<section class="card">
    <div class="card-body">
        <div class="row g-3"><x-shared.form.controls.input name="name" label="الاسم" :value="old('name', $user->name)"
                required /><x-shared.form.controls.input name="email" type="email" label="البريد" :value="old('email', $user->email)"
                required /><x-shared.form.controls.input name="phone" label="الجوال"
                :value="old('phone', $user->phone)" /><x-shared.form.controls.password name="password" :label="$user->exists ? 'كلمة مرور جديدة' : 'كلمة المرور'"
                :required="!$user->exists" /><x-shared.form.controls.password name="password_confirmation"
                label="تأكيد كلمة المرور" :required="!$user->exists" /><x-shared.form.controls.select name="status"
                label="الحالة" :options="['active' => 'نشط', 'inactive' => 'غير نشط']" :value="old('status', $user->status ?: 'active')" required />
            <div class="col-12">
                @foreach ($roles as $role)
                    <label class="form-check d-inline-block me-3"><input type="checkbox" class="form-check-input"
                            name="role_ids[]" value="{{ $role->id }}"
                            @checked(in_array($role->id, old('role_ids', $user->roles->pluck('id')->all()), true))>{{ $role->display_name }}</label>
                @endforeach
            </div>
        </div>
    </div>
</section><button class="btn btn-primary mt-4">حفظ</button>
