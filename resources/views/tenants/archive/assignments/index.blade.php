@extends('tenants.layouts.layoutMaster')

@section('title', 'أرشيف الإسنادات المحذوفة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
                    cancelButton: 'btn btn-secondary'
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
                                window.LaravelDataTables["assignments-archive-table"].ajax
                                    .reload();
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
    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'assignments-archive-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
