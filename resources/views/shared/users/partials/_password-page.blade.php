@if ($requiredPasswordChange)
    <x-bootstrap.modal id="initial-password-modal" title="تغيير كلمة المرور" icon="key" size="lg" :static-backdrop="true"
        :dismissible="false">
        <p class="text-muted">يجب تغيير كلمة المرور قبل استخدام النظام للمتابعة إلى لوحة التحكم.</p>
        @include('shared.users.partials._password-form', ['justify' => 'end'])
    </x-bootstrap.modal>
@else
    @include('shared.users.partials._password-form')
@endif
