@extends('tenants.layouts.layoutMaster')

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
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/user-mangment/user/pages-profile.js', 'resources/assets/js/tenants/user-mangment/user/user-sessions.js'])
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
                    <img src="{{ $user->background_image ? tasset($user->background_image) : asset('img/cta-bg-light.png') }}"
                        alt="صورة الغلاف" class="rounded-top" style="width: 100%; height: 100%; object-fit: cover;">
                    @if (auth()->id() === $user->id || auth()->user()->can('تعديل مستخدم'))
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
                        @if (auth()->id() === $user->id || auth()->user()->can('تعديل مستخدم'))
                            <button type="button"
                                class="btn btn-sm btn-icon btn-primary rounded-circle position-absolute bottom-0 end-0 me-4 mb-1"
                                data-bs-toggle="modal" data-bs-target="#changeProfileImageModal"
                                style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
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
                                    @if ($user->branch)
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class="ti ti-building ti-lg text-primary"></i>
                                            <span class="fw-bold small">{{ $user->branch->name }}</span>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal لتغيير الصورة الشخصية -->
            <div class="modal fade" id="changeProfileImageModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">تغيير الصورة الشخصية</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="changeProfileImageForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="text-center mb-4">
                                    <img id="previewImage" src="{{ tasset($user->image) }}" alt="معاينة الصورة"
                                        class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                                </div>
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">اختر صورة جديدة</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image"
                                        accept="image/*">
                                    <div class="form-text">الصيغ المسموح بها: JPG, PNG, GIF. الحد الأقصى: 2MB</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary"
                                    data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-primary">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-device-floppy me-2"></i>
                                        حفظ التغييرات
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- edit background imag -->
            <div class="modal fade" id="editBackgroundImageModal" tabindex="-1"
                aria-labelledby="editBackgroundImageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog">
                    <form id="changeBackgroundImageForm" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header pb-5">
                                <h5 class="modal-title" id="editBackgroundImageModalLabel">تحديث صورة الخلفية</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="إغلاق"></button>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="background_image" class="form-label">اختر صورة خلفية جديدة</label>
                                    <small class="text-danger">يجب أن تكون أبعاد صورة الخلفية 1693 × 376 بكسل</small>
                                    <input class="form-control" type="file" id="background_image"
                                        name="background_image" accept="image/*" required>
                                </div>
                                <div class="mb-3">
                                    <img id="previewBackgroundImage"
                                        src="{{ $user->background_image ? asset('storage/' . $user->background_image) : asset('assets/img/pages/profile-banner.png') }}"
                                        alt="صورة الخلفية المعاينة" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" id="submitBackgroundBtn">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-device-floppy me-2"></i>
                                        تحديث
                                    </span>
                                </button>
                                <button type="button" class="btn btn-secondary mx-0"
                                    data-bs-dismiss="modal">إغلاق</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="nav-align-top">
                        <ul class="nav nav-pills mb-4 gap-2"
                            style="background: #fff; padding: 8px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#general_info_tab"
                                    style="padding: 6px 16px; font-size: 0.8125rem; border-radius: 6px;">
                                    <i class="ti ti-user-check" style="font-size: 0.875rem;"></i>
                                    <span class="d-none d-sm-inline ms-1">البيانات الأساسية</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#activity_tab"
                                    style="padding: 6px 16px; font-size: 0.8125rem; border-radius: 6px;">
                                    <i class="ti ti-activity" style="font-size: 0.875rem;"></i>
                                    <span class="d-none d-sm-inline ms-1">النشاطات</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#sessions_tab"
                                    style="padding: 6px 16px; font-size: 0.8125rem; border-radius: 6px;">
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">الاسم الكامل</div>
                                            <div class="fw-semibold" style="font-size: 0.875rem;">{{ $user->name }}
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">البريد الإلكتروني
                                            </div>
                                            <div class="fw-semibold" style="font-size: 0.875rem;">{{ $user->email }}
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">رقم الهاتف</div>
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">الرقم الوظيفي</div>
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
                                                <span class="avatar-initial rounded bg-label-secondary">
                                                    <i class="ti ti-building"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">الفرع</div>
                                            <div class="fw-semibold" style="font-size: 0.875rem;">
                                                @if ($user->branch)
                                                    <span
                                                        class="badge bg-label-secondary">{{ $user->branch->name }}</span>
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
                                                <span
                                                    class="avatar-initial rounded {{ $user->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }}">
                                                    <i class="ti ti-{{ $user->status === 'active' ? 'check' : 'x' }}"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">حالة الحساب</div>
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">الدور الوظيفي</div>
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
                                            <div class="text-muted mb-1" style="font-size: 0.75rem;">تاريخ التسجيل</div>
                                            <div class="fw-semibold" style="font-size: 0.875rem;">
                                                {{ $user->created_at->locale('ar')->translatedFormat('d F Y') }}</div>
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
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('profile_image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('previewImage').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('changeProfileImageForm').addEventListener('submit', function(e) {
                e.preventDefault();

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

            document.getElementById('changeBackgroundImageForm').addEventListener('submit', function(e) {
                e.preventDefault();

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
