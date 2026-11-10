@if ($requiredPasswordChange)
    <x-bootstrap.modal id="initial-password-modal" title="تغيير كلمة المرور" icon="key" size="lg" :static-backdrop="true"
        :dismissible="false">
        <p class="text-muted">يجب تغيير كلمة المرور قبل استخدام النظام. بعد الحفظ، سيتم تحويلك لصفحة تسجيل الدخول
            للتسجيل بكلمة المرور الجديدة.</p>
        @include('shared.users.partials._password-form', ['justify' => 'end'])
    </x-bootstrap.modal>
@else
    @include('shared.users.partials._password-form')
@endif
