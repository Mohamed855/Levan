@extends('admin.layouts.layoutMaster')

@section('title', 'الملف الشخصي للمستخدم')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
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
    @vite(['resources/assets/js/tenants/user-mangment/user/pages-profile.js'])
@endsection


@section('content')

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">

                <div class="background-image-container"
                    style="position: relative; width: 100%; height: 100%; overflow: hidden;">
                    <img src="{{ $user->background_image ? asset('storage/' . $user->background_image) : asset('profile.jpg') }}"
                        alt="صورة الغلاف" class="rounded-top" style="width: 100%; height: 143px !important; object-fit: cover;">
                    @if (auth()->guard('admin')->check() && auth()->guard('admin')->id() === $user->id)
                        <button type="button" class="btn btn-primary btn-sm edit-button" data-bs-toggle="modal"
                            data-bs-target="#editBackgroundImageModal"
                            style="position: absolute; top: 10px; right: 10px; opacity: 0; transition: opacity 0.3s;">
                            <i class="ti ti-pencil"></i>تغيير
                        </button>
                    @endif
                </div>
                <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-5">
                    <div class="profile-image-container mt-n5 mx-sm-0 mx-auto"
                        style="position: relative; display: inline-block;">
                        <img src="{{ $user->profile_picture && Storage::disk('public')->exists($user->profile_picture) ? asset('storage/' . $user->profile_picture) : asset('assets/img/branding/logo.svg') }}"
                            alt="صورة الموظف" class="d-block h-auto ms-0 ms-sm-6"
                            style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%;">

                        <!-- زر تحديث الصورة الشخصية -->
                        @if (auth()->guard('admin')->check() && auth()->guard('admin')->id() === $user->id)
                            <button type="button" class="btn btn-primary btn-sm edit-button" data-bs-toggle="modal"
                                data-bs-target="#editProfilePictureModal"
                                style="margin-right: 30px; position: absolute; bottom: 0; right: 0; opacity: 0; transition: opacity 0.3s;">
                                <i class="ti ti-pencil"></i>
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
                                    @isset($role)
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class='ti ti-id-badge fs-5 text-primary'></i>
                                            <span class="fw-bold small">
                                                {{ $role->name }}
                                            </span>
                                        </li>
                                    @endisset

                                    @isset($user->created_at)
                                        <li class="list-inline-item d-flex gap-2 align-items-center">
                                            <i class='ti ti-calendar-event fs-5 text-primary'></i>
                                            <span class="fw-bold small">
                                                تاريخ الإضافة
                                                {{ optional($user->created_at)->format('Y-m-d') ?? '' }}
                                            </span>
                                        </li>
                                    @endisset

                                </ul>
                            </div>

                        </div>
                    </div>
                </div>

            </div>


            <!-- edit profile imag -->
            <div class="modal fade" id="editProfilePictureModal" tabindex="-1"
                aria-labelledby="editProfilePictureModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog">
                    {{-- <form action="{{ route('account.employee.profile.picture.update', $user->id) }}" method="POST" --}}
                    <form action="" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header pb-5">
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="إغلاق"></button>
                                <h5 class="modal-title" id="editProfilePictureModalLabel">تحديث الصورة الشخصية</h5>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="profile_picture" class="form-label">اختر صورة جديدة</label>
                                    <input class="form-control" type="file" id="profile_picture" name="profile_picture"
                                        accept="image/*" required>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <img id="previewImage"
                                        src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : asset('assets/img/branding/logo.svg') }}"
                                        alt="صورة المعاينة" class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">تحديث</button>
                                <button type="button" class="btn btn-secondary mx-0" data-bs-dismiss="modal">إغلاق</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- edit background imag -->
            <div class="modal fade" id="editBackgroundImageModal" tabindex="-1"
                aria-labelledby="editBackgroundImageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog">
                    {{-- <form action="{{ route('account.employee.profile.background.update', $user->id) }}" method="POST" --}}
                    <form action="" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
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
                                <button type="submit" class="btn btn-primary">تحديث</button>

                                <button type="button" class="btn btn-secondary mx-0"
                                    data-bs-dismiss="modal">إغلاق</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade show active" id="general_info_tab">
                <div class="row g-3 d-flex align-items-stretch">
                    <div class="col-xl-8">
                        <!-- معلومات عن الموظف -->
                        <div class="card mb-6">
                            <div class="card-header py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-list-details text-warning me-2"></i>
                                    البيانات الاساسية
                                </h6>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body row">
                                <div class="col-6">
                                    <small class="card-text text-primary fw-bold">معلومات الموظف</small>
                                    <ul class="list-unstyled my-3 py-1">
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-user-circle  text-primary"></i>
                                            <small class="mx-2 fw-semibold">
                                                {{ $user->name }}</small>
                                        </li>

                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-id-badge  text-primary"></i>
                                            <small class="mx-2 fw-semibold">{{ $role ? $role->name : 'غير متوفر' }}</small>
                                        </li>

                                    </ul>
                                </div>

                                <div class="col-6">
                                    <small class="card-text text-primary fw-bold">التواصل</small>
                                    <ul class="list-unstyled my-3 py-1">
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-phone text-primary"></i>
                                            <small class="mx-2 fw-semibold">{{ $user->phone ?? 'غير محدد' }}</small>
                                        </li>
                                        <li class="d-flex align-items-center mb-4">
                                            <i class="ti ti-mail text-primary"></i>
                                            <small class="mx-2 fw-semibold">{{ $user->email ?? 'غير محدد' }}</small>
                                        </li>
                                    </ul>
                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="col-xl-4">
                        <!-- سجل النشاطات -->
                        <div class="card mb-6">
                            <div class="card-header d-flex justify-content-between py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-history text-warning me-2"></i>
                                    سجل النشاطات
                                </h6>
                                <h6 class="small mb-0">اخر 3 نشاطات</h6>

                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body pt-3 small fw-bold">
                                <ul class="timeline mb-0">
                                    @forelse ($recentActivities as $activity)
                                        <li class="timeline-item">
                                            <span class="timeline-point bg-secondary"></span>
                                            <div class="timeline-event">
                                                <small class="timeline-title text-capitalize">
                                                    {{ $activity->description }}
                                                </small>
                                                <small class="text-muted d-block">
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </li>
                                    @empty
                                        <h6 class="mb-0 small text-center">لا توجد نشاطات</h6>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <!--/ سجل النشاطات -->
                    </div>
                </div>

                <!-- قسم الكوبونات -->
                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header py-4 d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-ticket text-success me-2"></i>
                                    الكوبونات المُسندة
                                </h6>
                                <span class="badge bg-label-primary rounded-pill">{{ $coupons->count() }} كوبون</span>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>

                            <div class="card-body">
                                @forelse ($coupons as $coupon)
                                    @php
                                        $expiryDate = $coupon->expiry_date ? \Carbon\Carbon::parse($coupon->expiry_date) : null;
                                        $isExpired = $expiryDate && $expiryDate->isPast();
                                        $isExhausted = $coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit;
                                    @endphp
                                    
                                    <div class="card mb-3 border border-2 {{ $coupon->is_active && !$isExpired && !$isExhausted ? 'border-primary' : 'border-secondary' }}" style="border-radius: 12px; overflow: hidden;">
                                        <div class="card-body p-4">
                                            <!-- الصف الأول: العنوان والحالة -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <h5 class="mb-0 fw-bold text-dark">
                                                            {{ $coupon->name }}
                                                        </h5>
                                                        <i class="ti ti-discount-2 text-primary fs-4"></i>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="text-muted small">الكود:</span>
                                                        <code class="bg-light px-2 py-1 rounded fw-bold" style="font-size: 14px;">{{ $coupon->code }}</code>
                                                        <i class="ti ti-copy text-primary cursor-pointer" onclick="navigator.clipboard.writeText('{{ $coupon->code }}')" title="نسخ الكود"></i>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-end">
                                                    @if(!$coupon->is_active)
                                                        <span class="badge bg-secondary rounded-pill mb-1">
                                                            <i class="ti ti-circle-x me-1"></i>
                                                            غير نشط
                                                        </span>
                                                    @elseif($isExpired)
                                                        <span class="badge bg-danger rounded-pill mb-1">
                                                            <i class="ti ti-alert-circle me-1"></i>
                                                            منتهي
                                                        </span>
                                                    @elseif($isExhausted)
                                                        <span class="badge bg-warning rounded-pill mb-1">
                                                            <i class="ti ti-alert-triangle me-1"></i>
                                                            مستنفذ
                                                        </span>
                                                    @else
                                                        <span class="badge bg-success rounded-pill mb-1">
                                                            <i class="ti ti-circle-check me-1"></i>
                                                            نشط
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <hr class="my-3">

                                            <!-- الصف الثاني: المعلومات الأساسية -->
                                            <div class="row g-3">
                                                <!-- قيمة الخصم -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-percentage text-success fs-5"></i>
                                                        <small class="text-muted">قيمة الخصم</small>
                                                    </div>
                                                    <div class="fw-bold fs-5 text-success">
                                                        @if($coupon->discount_type === 'fixed')
                                                            {{ number_format($coupon->discount_value, 0) }} جنيه
                                                        @else
                                                            {{ number_format($coupon->discount_value, 0) }}%
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- حد الاستخدام -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-users text-info fs-5"></i>
                                                        <small class="text-muted">حد الاستخدام</small>
                                                    </div>
                                                    <div class="fw-bold fs-5 {{ $isExhausted ? 'text-danger' : 'text-info' }}">
                                                        @if($coupon->usage_limit)
                                                            {{ $coupon->used_count }} / {{ $coupon->usage_limit }}
                                                            <i class="ti ti-user-check fs-6"></i>
                                                        @else
                                                            غير محدود
                                                            <i class="ti ti-infinity fs-6"></i>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- عدد مرات الاستخدام -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-chart-bar text-warning fs-5"></i>
                                                        <small class="text-muted">عدد مرات الاستخدام</small>
                                                    </div>
                                                    <div class="fw-bold fs-5 text-warning">
                                                        {{ $coupon->used_count }}
                                                        <i class="ti ti-ticket fs-6"></i>
                                                    </div>
                                                </div>

                                                <!-- تاريخ الانتهاء -->
                                                <div class="col-md-3">
                                                    <div class="d-flex align-items-center gap-2 mb-1">
                                                        <i class="ti ti-calendar-time {{ $isExpired ? 'text-danger' : 'text-primary' }} fs-5"></i>
                                                        <small class="text-muted">تاريخ الانتهاء</small>
                                                    </div>
                                                    @if($expiryDate)
                                                        <div class="fw-bold fs-6 {{ $isExpired ? 'text-danger' : 'text-primary' }}">
                                                            {{ $expiryDate->locale('ar')->isoFormat('D MMMM YYYY') }}
                                                        </div>
                                                        <small class="text-muted">
                                                            @if($isExpired)
                                                                منذ {{ $expiryDate->locale('ar')->diffForHumans() }}
                                                            @else
                                                                {{ $expiryDate->locale('ar')->diffForHumans() }}
                                                            @endif
                                                        </small>
                                                    @else
                                                        <div class="fw-bold fs-6 text-success">
                                                            بدون تاريخ انتهاء
                                                            <i class="ti ti-infinity"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($coupon->max_discount)
                                                <div class="alert alert-info mt-3 mb-0 py-2" role="alert">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    <small>الحد الأقصى للخصم: <strong>{{ number_format($coupon->max_discount, 0) }}</strong> جنيه</small>
                                                </div>
                                            @endif

                                            <!-- التواريخ السفلية -->
                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                                <small class="text-muted">
                                                    <i class="ti ti-calendar-plus me-1"></i>
                                                    تاريخ الإنشاء: 
                                                    <span class="fw-semibold">{{ $coupon->created_at->locale('ar')->isoFormat('D MMMM YYYY') }}</span>
                                                </small>
                                                
                                                @if($coupon->updated_at && $coupon->updated_at->ne($coupon->created_at))
                                                    <small class="text-muted">
                                                        <i class="ti ti-refresh me-1"></i>
                                                        آخر تحديث: 
                                                        <span class="fw-semibold">{{ $coupon->updated_at->locale('ar')->diffForHumans() }}</span>
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-5">
                                        <div class="mb-3">
                                            <i class="ti ti-ticket-off text-muted" style="font-size: 80px; opacity: 0.3;"></i>
                                        </div>
                                        <h5 class="text-muted mb-2">لا توجد كوبونات</h5>
                                        <p class="text-muted small">لم يتم إسناد أي كوبونات لهذا المستخدم حتى الآن</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ قسم الكوبونات -->

            </div>



        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // إضافة تأثير إظهار الأزرار عند المرور بالماوس
                const backgroundContainer = document.querySelector('.background-image-container');
                const backgroundEditButton = backgroundContainer.querySelector('.edit-button');

                backgroundContainer.addEventListener('mouseover', () => {
                    backgroundEditButton.style.opacity = '1';
                });

                backgroundContainer.addEventListener('mouseout', () => {
                    backgroundEditButton.style.opacity = '0';
                });

                const profileContainer = document.querySelector('.profile-image-container');
                const profileEditButton = profileContainer.querySelector('.edit-button');

                profileContainer.addEventListener('mouseover', () => {
                    profileEditButton.style.opacity = '1';
                });

                profileContainer.addEventListener('mouseout', () => {
                    profileEditButton.style.opacity = '0';
                });
            });

            document.getElementById('background_image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                const img = new Image();
                img.src = URL.createObjectURL(file);

                img.onload = function() {
                    if (this.width !== 1693 || this.height !== 376) {
                        Swal.fire({
                            icon: 'error',
                            text: 'يجب أن تكون أبعاد صورة الخلفية 1693x376 بكسل.',
                            customClass: {
                                confirmButton: 'btn btn-success waves-effect waves-light'
                            },
                            confirmButtonText: 'حسناً'
                        });
                        e.target.value = ''; // إعادة تعيين حقل الإدخال
                    }
                };
            });
        </script>

        <style>
            .cursor-pointer {
                cursor: pointer;
                transition: all 0.2s ease;
            }
            .cursor-pointer:hover {
                transform: scale(1.2);
                color: #007bff !important;
            }
            code {
                letter-spacing: 1px;
            }
            .card {
                transition: all 0.3s ease;
            }
            .card:hover {
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
            }
        </style>
    </div>
@endsection