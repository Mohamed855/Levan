@extends('admin.layouts.layoutMaster')

@section('title', 'الكوبونات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/coupons/filter.js', 'resources/assets/js/admin/coupons/index.js'])
    <script>
        function copyTextToClipboard(text, onSuccess, onError) {
            if (!text) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    if (typeof onSuccess === 'function') onSuccess();
                }).catch(function() {
                    fallbackCopyText(text, onSuccess, onError);
                });
            } else {
                fallbackCopyText(text, onSuccess, onError);
            }
        }

        function fallbackCopyText(text, onSuccess, onError) {
            try {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.top = '-9999px';
                textArea.style.left = '-9999px';
                textArea.setAttribute('readonly', '');
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                textArea.setSelectionRange(0, textArea.value.length);
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);

                if (successful) {
                    if (typeof onSuccess === 'function') onSuccess();
                } else if (typeof onError === 'function') {
                    onError();
                }
            } catch (err) {
                if (typeof onError === 'function') onError();
            }
        }

        $(document).ready(function() {
            $(document).on('click', '.copyable-code-wrapper, .copyable-code, .copy-icon', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $wrapper = $(this).closest('.copyable-code-wrapper');
                const code = ($wrapper.length ? $wrapper.data('code') : null) || $(this).data('code') || $(this).text().trim();
                const $icon = $wrapper.length ? $wrapper.find('.copy-icon, .ti-copy') : $(this).parent().find('.ti-copy, .copy-icon');

                if (!code) return;

                copyTextToClipboard(code, function() {
                    const originalClass = $icon.attr('class') || 'ti ti-copy text-muted copy-icon';
                    $icon.removeClass('ti-copy text-muted').addClass('ti ti-check text-success');

                    if (typeof toastr !== 'undefined') {
                        toastr.success('تم نسخ الكود: ' + code, 'نجح!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 2000,
                            positionClass: 'toast-top-right'
                        });
                    }

                    setTimeout(function() {
                        $icon.attr('class', originalClass);
                    }, 2000);
                }, function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('تعذر نسخ الكود');
                    } else {
                        alert('تعذر نسخ الكود: ' + code);
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الكوبونات" :value="$totalPlans" subtitle="إجمالي الكوبونات"
            icon="ti-ticket" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الكوبونات النشطة" :value="$activecoupons" subtitle="الكوبونات النشطة"
            icon="ti-rosette-discount" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الكوبونات الغير نشطة" :value="$inactivecoupons"
            subtitle="الكوبونات الغير نشطة" icon="ti-rosette-discount-off" color="warning" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="عدد الكوبونات المستخدمة" :value="$couponCount"
            subtitle="عدد الكوبونات المستخدمة" icon="ti-discount-check" color="danger" />
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="filters row g-3">

                <div class="col-md-6">
                    <label for="filter-status">حالة الكوبونات</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر  حالة الكوبون">
                        <option value=""></option>
                        {{-- @foreach ($advanceStatus as $status) --}}
                        <option value="1">نشط</option>
                        <option value="0">غير نشط</option>
                        {{-- @endforeach --}}
                    </select>
                </div>


                <div class="col-md-6">
                    <label for="filter-type">نوع الخصم</label>
                    <select id="filter-type" class="form-control select2" data-placeholder="اختر  نوع الخصم">
                        <option value=""></option>
                        {{-- @foreach ($advanceStatus as $status) --}}
                        <option value="percentage">نسبة مئوية</option>
                        <option value="fixed">نسبة رقمية</option>
                        {{-- @endforeach --}}
                    </select>
                </div>
            </div>
            <hr class="mt-10">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>



@endsection
