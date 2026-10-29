@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء عميل جديد')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/clients/form-ui.js'])
@endsection

@section('content')
    @if (!empty($trialPrefill))
        <script>
            window.trialPrefill = @json($trialPrefill);
            document.addEventListener('DOMContentLoaded', function() {
                Object.entries(window.trialPrefill).forEach(([name, value]) => {
                    const input = document.querySelector(`[name="${name}"]`);
                    if (input && !input.value) input.value = value;
                });
            });
        </script>
    @endif

    <div class="row">
        <div class="col-12">
            @include('admin.clients.partials._form', ['is_edit' => false])
        </div>
    </div>
@endsection
