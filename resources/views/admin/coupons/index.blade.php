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
        $(document).ready(function() {
            // استخدام event delegation على body أو على الجدول مباشرة
            $(document).on('click', '.copyable-code', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const code = $(this).data('code');
                const $icon = $(this).parent().find('.ti-copy');

                console.log('Copying code:', code); // للتأكد من أن الحدث يعمل

                // نسخ النص
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(code).then(function() {
                        // تغيير الأيقونة مؤقتاً
                        const originalClass = $icon.attr('class');
                        $icon.removeClass().addClass('ti ti-check text-success');

                        // إظهار رسالة Toast
                        if (typeof toastr !== 'undefined') {
                            toastr.success('تم نسخ الكود: ' + code, 'نجح!', {
                                closeButton: true,
                                progressBar: true,
                                timeOut: 2000,
                                positionClass: 'toast-top-right'
                            });
                        } else {
                            showCopyNotification(code);
                        }

                        // إعادة الأيقونة بعد ثانيتين
                        setTimeout(function() {
                            $icon.removeClass().addClass(originalClass);
                        }, 2000);

                    }).catch(function(err) {
                        console.error('فشل النسخ:', err);
                        fallbackCopyTextToClipboard(code);
                    });
                } else {
                    // استخدام الطريقة البديلة مباشرة
                    fallbackCopyTextToClipboard(code, $icon);
                }
            });
        });

        // دالة بديلة للنسخ (للمتصفحات القديمة)
        function fallbackCopyTextToClipboard(text, $icon) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    if ($icon && $icon.length) {
                        const originalClass = $icon.attr('class');
                        $icon.removeClass().addClass('ti ti-check text-success');

                        setTimeout(function() {
                            $icon.removeClass().addClass(originalClass);
                        }, 2000);
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success('تم نسخ الكود: ' + text, 'نجح!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 2000,
                            positionClass: 'toast-top-right'
                        });
                    } else {
                        showCopyNotification(text);
                    }
                }
            } catch (err) {
                console.error('فشل النسخ:', err);
                alert('فشل نسخ الكود. الرجاء المحاولة مرة أخرى.');
            }

            document.body.removeChild(textArea);
        }

        // إظهار إشعار بسيط إذا لم يكن toastr موجود
        function showCopyNotification(code) {
            const notification = document.createElement('div');
            notification.innerHTML = `
                <div style="position: fixed; top: 20px; right: 20px; z-index: 9999; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideIn 0.3s ease;">
                    <i class="ti ti-check me-2"></i>
                    تم نسخ الكود: <strong>${code}</strong>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(function() {
                notification.firstElementChild.style.animation = 'slideOut 0.3s ease';
                setTimeout(function() {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 2000);
        }
    </script>
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <!-- بطاقات الإحصائيات -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الكوبونات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalPlans }}</h4>
                            </div>
                            <small class="mb-0">إجمالي الكوبونات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-ticket ti-26px"></i>

                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الكوبونات النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activecoupons }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي الكوبونات النشطة </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-discount-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الكوبونات الغير نشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactivecoupons }}</h4>
                            </div>
                            <small class="mb-0"> الكوبونات الغير نشطة</small>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-discount-off ti-26px"></i>

                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">عدد الكوبونات المستخدمة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $couponCount }}</h4>
                            </div>
                            <small class="mb-0">إجمالي الكوبونات المستخدمة</small>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-discount-off ti-26px"></i>

                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


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
