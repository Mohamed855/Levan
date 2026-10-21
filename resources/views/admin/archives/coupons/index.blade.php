@extends('layouts.dashboard')

@section('title', 'أرشيف الكوبونات')
@section('dashboard-scope', 'admin')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-script')
    <script>
        $(function () {
            $(document).on('click', '.js-restore-btn', function () {
                const url = $(this).data('url');
                Swal.fire({
                    title: 'تأكيد الاسترجاع',
                    text: 'هل أنت متأكد من استرجاع هذا الكوبون من الأرشيف؟',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، استرجع',
                    cancelButtonText: 'إلغاء',
                    customClass: {
                        confirmButton: 'btn btn-success me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = `@csrf @method('PATCH')`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.js-force-delete-btn', function () {
                const url = $(this).data('url');
                Swal.fire({
                    title: 'تحذير: حذف نهائي',
                    text: 'سيتم حذف هذا الكوبون نهائياً ولا يمكن استرجاعه أبداً!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، احذف نهائياً',
                    cancelButtonText: 'إلغاء',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        form.innerHTML = `@csrf @method('DELETE')`;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-archive text-warning me-2"></i>
                أرشيف الكوبونات المحذوفة
            </h5>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للكوبونات النشطة
            </a>
        </div>
        <div class="card-body pt-4">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'coupons-archive-table'], false) !!}
        </div>
    </div>
@endsection
