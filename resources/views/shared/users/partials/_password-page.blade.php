@if ($requiredPasswordChange)
    <x-bootstrap.modal id="initial-password-modal" title="تغيير كلمة المرور" icon="key" size="lg"
        :static-backdrop="true" :dismissible="false">
        <p class="text-muted">يجب تغيير كلمة المرور المؤقتة قبل استخدام النظام. بعد الحفظ، سجّل الدخول بكلمة المرور الجديدة.</p>
        @include('shared.users.partials._password-form')
    </x-bootstrap.modal>
@else
    @include('shared.users.partials._password-form')
@endif
