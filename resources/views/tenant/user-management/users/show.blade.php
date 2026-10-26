@extends('tenant.layouts.layoutMaster')

@section('title', 'الملف الشخصي للمستخدم')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-profile.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/user-management/user/pages-profile.js', 'resources/assets/js/tenant/user-management/user/user-sessions.js', 'resources/assets/js/shared/form-validation.js'])
    <script>
        window.sessionsData = @json($sessionsData ?? ['days' => [], 'statistics' => []]);
        window.userId = {{ $user->id }};
    </script>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-6">


                <div class="background-image-container"
                    style="position: relative; width: 100%; height: 100%; overflow: hidden;">
                    <img src="{{ $user->background_image ? tasset($user->background_image) : asset('static/dashboard/backgrounds/cta-bg-secondary.png') }}"
                        alt="صورة الغلاف" class="rounded-top" style="width: 100%; height: 100%; object-fit: cover;">
                    @if (auth()->id() === $user->id || auth()->user()->can('users.edit'))
                        <button type="button" class="btn btn-primary btn-sm edit-button" data-bs-toggle="modal"
                            data-bs-target="#editBackgroundImageModal"
                            style="position: absolute; top: 10px; right: 10px; transition: opacity 0.3s;">
                            <i class="ti ti-pencil"></i>تغيير
                        </button>
                    @endif
                </div>

                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-5">
                    <div class="profile-image-container mt-n5 mx-sm-0 mx-auto position-relative"
                        style="display: inline-block;">
                        <img src="{{ tasset($user->image) }}" alt="صورة المستخدم"
                            class="d-block h-auto ms-0 ms-sm-6 rounded user-profile-img"
                            style="width: 150px; height: 150px; object-fit: cover; border: none !important; outline: none !important; box-shadow: none !important;">

                        <!-- زر تعديل الصورة -->
                        @if (auth()->id() === $user->id || auth()->user()->can('users.edit'))
                            <button type="button"
                                class="btn btn-icon btn-primary rounded-circle position-absolute bottom-0 end-0 me-4 mb-1"
                                data-bs-toggle="modal" data-bs-target="#changeProfileImageModal"
                                style="width: 2rem; height: 2rem; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="ti ti-pencil ti-14px"></i>
                            </button>
                        @endif
                    </div>

                    <div class="flex-grow-1 mt-3 mt-lg-5">
                        <div
                            class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                            <div class="user-profile-info">
                                <h5 class="mb-2 mt-lg-6 fw-bold">{{ $user->name }}</h5>
                                <ul
                                    class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                    <li class="list-inline-item d-flex gap-2 align-items-center">
                                        <i class="ti ti-mail ti-lg text-primary"></i>
                                        <span class="fw-bold small">{{ $user->email }}</span>
                                    </li>
                                    @if ($user->phone)
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class="ti ti-phone ti-lg text-primary"></i>
                                            <span class="fw-bold small">{{ $user->phone }}</span>
                                        </li>
                                    @endif

                                </ul>
                            </div>
                        </div>
                        <!-- Modal لتغيير الصورة الشخصية -->
                        <div class="modal fade" id="changeProfileImageModal" tabindex="-1"
                            aria-labelledby="changeProfileImageModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="changeProfileImageModalLabel">
                                            <i class="ti ti-photo text-primary me-2"></i>تغيير الصورة الشخصية
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="إغلاق"></button>
                                    </div>
                                    <form id="changeProfileImageForm" method="POST" enctype="multipart/form-data"
                                        novalidate data-form-validate data-validation-submit="ajax"
                                        action="{{ tenant_route_name('users.update-image', $user->id) }}"
                                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                                        @csrf
                                        <div class="modal-body">
                                            <x-form.controls.file name="profile_image" id="profile_image" label="اختر صورة شخصية جديدة"
                                                :value="$user->image ? tasset($user->image) : null"
                                                accept="image/*" help="الصيغ المسموح بها: JPG, PNG, GIF. الحد الأقصى: 2MB"
                                                :max-size="2" col="col-12" />
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary"
                                                data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ti ti-device-floppy me-1"></i> حفظ التغييرات
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- edit background imag -->
                        <div class="modal fade" id="editBackgroundImageModal" tabindex="-1"
                            aria-labelledby="editBackgroundImageModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form id="changeBackgroundImageForm" method="POST" enctype="multipart/form-data" novalidate
                                    data-form-validate data-validation-submit="ajax"
                                    action="{{ tenant_route_name('users.update-background', $user->id) }}"
                                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                                    @csrf
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editBackgroundImageModalLabel">
                                                <i class="ti ti-photo-edit text-primary me-2"></i>تحديث صورة الخلفية
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="إغلاق"></button>
                                        </div>

                                        <div class="modal-body">
                                            <x-form.controls.file name="background_image" id="background_image" label="اختر صورة خلفية جديدة"
                                                :value="$user->background_image ? asset('storage/' . $user->background_image) : asset('static/dashboard/backgrounds/cta-bg-secondary3.png')"
                                                accept="image/*" required help="الأبعاد الموصى بها: 1693 × 376 بكسل"
                                                :max-size="5" col="col-12" />
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-label-secondary"
                                                data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-primary" id="submitBackgroundBtn">
                                                <i class="ti ti-device-floppy me-1"></i> تحديث
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="nav-align-top">
                                    <ul class="nav nav-pills mb-4 gap-2"
                                        style="background: #fff; padding: 0.5rem; border-radius: 0.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#general_info_tab"
                                                style="padding: 6px 1rem; font-size: 0.8125rem; border-radius: 6px;">
                                                <i class="ti ti-user-check" style="font-size: 0.875rem;"></i>
                                                <span class="d-none d-sm-inline ms-1">البيانات الأساسية</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#activity_tab"
                                                style="padding: 6px 1rem; font-size: 0.8125rem; border-radius: 6px;">
                                                <i class="ti ti-activity" style="font-size: 0.875rem;"></i>
                                                <span class="d-none d-sm-inline ms-1">النشاطات</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#sessions_tab"
                                                style="padding: 6px 1rem; font-size: 0.8125rem; border-radius: 6px;">
                                                <i class="ti ti-clock" style="font-size: 0.875rem;"></i>
                                                <span class="d-none d-sm-inline ms-1">جلسات التحصيل</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content p-0">
                            <div class="tab-pane fade show active" id="general_info_tab">
                                <div class="card mb-6">
                                    <div class="card-header">
                                        <h5 class="mb-0">
                                            <i class="ti ti-id me-2"></i>
                                            البيانات الأساسية
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-primary">
                                                                <i class="ti ti-user"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">الاسم
                                                            الكامل</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            {{ $user->name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-info">
                                                                <i class="ti ti-mail"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">البريد
                                                            الإلكتروني
                                                        </div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            {{ $user->email }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-success">
                                                                <i class="ti ti-phone"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">رقم
                                                            الهاتف</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            {{ $user->phone ??
                                                                'غير
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        محدد' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-warning">
                                                                <i class="ti ti-id-badge"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">الرقم
                                                            الوظيفي</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            {{ $user->employee_number ?? 'غير محدد' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span
                                                                class="avatar-initial rounded {{ $user->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }}">
                                                                <i
                                                                    class="ti ti-{{ $user->status === 'active' ? 'check' : 'x' }}"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">حالة
                                                            الحساب</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            <span
                                                                class="badge bg-label-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                                                {{ $user->status === 'active' ? 'نشط' : 'غير نشط' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-primary">
                                                                <i class="ti ti-shield"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">الدور
                                                            الوظيفي</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            @if ($user->roles->isNotEmpty())
                                                                @foreach ($user->roles as $role)
                                                                    <span
                                                                        class="badge bg-label-primary me-1">{{ $role->name }}</span>
                                                                @endforeach
                                                            @else
                                                                <span class="text-muted">غير محدد</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="avatar avatar-sm">
                                                            <span class="avatar-initial rounded bg-label-info">
                                                                <i class="ti ti-calendar"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="text-muted mb-1" style="font-size: 0.75rem;">تاريخ
                                                            التسجيل</div>
                                                        <div class="fw-semibold" style="font-size: 0.875rem;">
                                                            {{ $user->created_at->locale('ar')->translatedFormat('d F Y') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="activity_tab">
                                <div class="card mb-6">
                                    <h5 class="card-header">آخر النشاطات</h5>
                                    <div class="card-body pt-1">
                                        <ul class="timeline mb-0">
                                            @foreach ($recentActivities as $activity)
                                                <li class="timeline-item timeline-item-transparent">
                                                    <span class="timeline-point timeline-point-primary"></span>
                                                    <div class="timeline-event">
                                                        <div class="timeline-header mb-3">
                                                            <h6 class="mb-0">{{ $activity->description }}</h6>
                                                            <small
                                                                class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endforeach

                                            @if ($recentActivities->isEmpty())
                                                <li class="timeline-item timeline-item-transparent">
                                                    <span class="timeline-point timeline-point-secondary"></span>
                                                    <div class="timeline-event">
                                                        <div class="timeline-header mb-3">
                                                            <h6 class="mb-0 text-muted">لا توجد نشاطات حتى الآن</h6>
                                                        </div>
                                                    </div>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="sessions_tab">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="ti ti-calendar me-2"></i>
                                            {{ now()->locale('ar')->translatedFormat('F Y') }}
                                        </h6>
                                    </div>
                                    <div class="card-body p-0" id="sessions-timeline-container">
                                        <div class="text-center py-5">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('changeProfileImageForm').addEventListener('formValidationPassed', function() {

                            const formData = new FormData(this);
                            const submitBtn = this.querySelector('button[type="submit"]');
                            const originalBtnHtml = submitBtn.innerHTML;

                            submitBtn.disabled = true;
                            submitBtn.innerHTML =
                                '<span class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>جاري الحفظ...</span>';

                            fetch(`{{ tenant_route_name('users.update-image', $user->id) }}`, {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        const modal = bootstrap.Modal.getInstance(document.getElementById(
                                            'changeProfileImageModal'));
                                        modal.hide();

                                        document.querySelector('.user-profile-img').src = data.image_url;

                                        toastr.success('تم تحديث الصورة الشخصية بنجاح', 'تم بنجاح');

                                        this.reset();
                                    } else {
                                        toastr.error(data.message || 'حدث خطأ أثناء تحديث الصورة', 'خطأ');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    toastr.error('حدث خطأ أثناء الاتصال بالخادم', 'خطأ');
                                })
                                .finally(() => {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalBtnHtml;
                                });
                        });

                        document.getElementById('changeBackgroundImageForm').addEventListener('formValidationPassed',
                            function() {

                                const formData = new FormData(this);
                                const submitBtn = document.getElementById('submitBackgroundBtn');
                                const originalBtnHtml = submitBtn.innerHTML;

                                submitBtn.disabled = true;
                                submitBtn.innerHTML =
                                    '<span class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>جاري الحفظ...</span>';

                                fetch(`{{ tenant_route_name('users.update-background', $user->id) }}`, {
                                        method: 'POST',
                                        body: formData,
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    })
                                    .then(response => {
                                        if (response.status === 422) {
                                            return response.json().then(data => {
                                                const errors = Object.values(data.errors).flat();
                                                toastr.error(errors[0], 'خطأ في التحقق');
                                                throw new Error('validation');
                                            });
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        if (data.success) {
                                            const modal = bootstrap.Modal.getInstance(document.getElementById(
                                                'editBackgroundImageModal'));
                                            modal.hide();
                                            document.querySelector('.background-image-container img').src = data
                                                .image_url + '?t=' + new Date().getTime();
                                            toastr.success('تم تحديث صورة الخلفية بنجاح', 'تم بنجاح');
                                            this.reset();
                                        } else {
                                            toastr.error(data.message || 'حدث خطأ أثناء تحديث الصورة', 'خطأ');
                                        }
                                    })
                                    .catch(error => {
                                        if (error.message !== 'validation') {
                                            toastr.error('حدث خطأ أثناء الاتصال بالخادم', 'خطأ');
                                        }
                                    })
                                    .finally(() => {
                                        submitBtn.disabled = false;
                                        submitBtn.innerHTML = originalBtnHtml;
                                    });
                            });

                    });
                </script>
            @endsection
