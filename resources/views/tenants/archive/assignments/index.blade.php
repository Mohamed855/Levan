@extends('tenants.layouts.layoutMaster')

@section('title', 'أرشيف الإسنادات المحذوفة')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        $(document).on('click', '.js-force-delete', function() {
            const url = $(this).data('url');
            Swal.fire({
                title: 'هل أنت متأكد من الحذف النهائي؟',
                text: "لن تتمكن من استرجاع هذا الإسناد مجدداً!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف نهائياً',
                cancelButtonText: 'إلغاء',
                customClass: {
                    confirmButton: 'btn btn-danger me-3',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                window.LaravelDataTables["assignments-archive-table"].ajax.reload();
                            }
                        },
                        error: function() {
                            toastr.error('حدث خطأ أثناء الحذف');
                        }
                    });
                }
            });
        });
    </script>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-trash me-2 text-danger"></i>أرشيف الإسنادات</h4>
            <p class="text-muted mb-0">عرض واستعادة الإسنادات المحذوفة أو حذفها نهائياً.</p>
        </div>
        <a href="{{ tenant_route_name('assignments.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للإسنادات
        </a>
    </div>

    <x-data-table.page table-id="assignments-archive-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-md-4" title="إجمالي المحذوفات" :value="$totalArchived" subtitle="إسنادات محذوفة"
                icon="ti-archive" color="danger" />
        </x-slot:stats>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'assignments-archive-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
