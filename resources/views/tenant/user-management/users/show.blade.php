@extends('tenant.layouts.layoutMaster')

@section('title', 'الملف الشخصي — ' . $user->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/user-management/user/user-sessions.js', 'resources/assets/js/shared/form-validation.js'])
    <script>
        window.sessionsData = @json($sessionsData ?? ['days' => [], 'statistics' => []]);
        window.userId = {{ $user->id }};
    </script>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-user me-2 text-primary"></i>الملف الشخصي: {{ $user->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                    {{ $user->status === 'active' ? 'نشط' : 'غير نشط' }}
                </span>
                @if ($user->isAdmin())
                    <span class="badge bg-primary">مدير النظام</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('users.edit')
                <a href="{{ tenant_route_name('users.edit', $user->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المستخدم
                </a>
            @endcan
            <a href="{{ tenant_route_name('users.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- العمود الأيمن (بطاقة المستخدم ومعلوماته) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3 position-relative" style="width: 100px; height: 100px;">
                        <img src="{{ $user->image ? tasset($user->image) : asset('static/brand/logos/logo.svg') }}"
                            alt="صورة المستخدم" class="rounded-circle border shadow-sm w-100 h-100 object-fit-cover"
                            onerror="this.onerror=null;this.src='{{ asset('static/brand/logos/logo.svg') }}';">
                        @if (auth()->id() === $user->id || auth()->user()?->can('users.edit'))
                            <button type="button" class="btn btn-sm btn-icon btn-primary rounded-circle position-absolute bottom-0 end-0"
                                data-bs-toggle="modal" data-bs-target="#changeProfileImageModal" title="تغيير الصورة الشخصية">
                                <i class="ti ti-edit ti-14px"></i>
                            </button>
                        @endif
                    </div>
                    <h5 class="mb-1 fw-bold">{{ $user->name }}</h5>
                    <div class="mb-3">
                        @if ($user->roles->isNotEmpty())
                            @foreach ($user->roles as $role)
                                <span class="badge bg-primary me-1">{{ \App\Support\AccessLabels::role($role->name) }}</span>
                            @endforeach
                        @else
                            <span class="badge bg-label-secondary">بدون دور</span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h6 class="mb-0 text-muted small">تاريخ التسجيل</h6>
                            <span class="fw-semibold">{{ $user->created_at->locale('ar')->translatedFormat('d F Y') }}</span>
                        </div>
                        <div>
                            <h6 class="mb-0 text-muted small">آخر دخول</h6>
                            <span class="fw-semibold">{{ $user->last_login_at ? $user->last_login_at->locale('ar')->diffForHumans() : 'لم يسجل' }}</span>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الاسم:</span>
                                <span class="text-muted">{{ $user->name }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-mail text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">البريد:</span>
                                <a href="mailto:{{ $user->email }}" class="text-truncate">{{ $user->email }}</a>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-phone text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الهاتف:</span>
                                <span class="text-muted" dir="ltr">{{ $user->phone ?? 'غير محدد' }}</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-shield-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الحالة:</span>
                                <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'danger' }}">
                                    {{ $user->status === 'active' ? 'نشط' : 'غير نشط' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- العمود الأيسر (جلسات العمل والنشاطات) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- كارت جلسات التحصيل --}}
            <div class="card mb-4" id="sessions_tab">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-clock text-primary me-2"></i> جلسات التحصيل
                    </h6>
                    <span class="badge bg-label-primary">{{ now()->locale('ar')->translatedFormat('F Y') }}</span>
                </div>
                <div class="card-body p-0" id="sessions-timeline-container">
                    <div class="text-center py-5">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
            </div>

            {{-- كارت آخر النشاطات --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-activity text-warning me-2"></i> آخر النشاطات
                    </h6>
                    <span class="badge bg-label-secondary">سجل العمليات</span>
                </div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        @forelse ($recentActivities as $activity)
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-1">
                                        <h6 class="mb-0">{{ $activity->description }}</h6>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-activity-off fs-2 d-block mb-2"></i>
                                لا توجد نشاطات مسجلة حتى الآن
                            </div>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال تعديل الصورة الشخصية --}}
    @if (auth()->id() === $user->id || auth()->user()?->can('users.edit'))
        <x-bootstrap.modal id="changeProfileImageModal" title="تغيير الصورة الشخصية" icon="ti-photo">
            <form id="changeProfileImageForm" method="POST" enctype="multipart/form-data" novalidate
                data-form-validate data-validation-submit="ajax"
                action="{{ tenant_route_name('users.update-image', $user->id) }}"
                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                <x-form.controls.file name="profile_image" id="profile_image" label="اختر صورة شخصية جديدة"
                    :value="$user->image ? tasset($user->image) : null" accept="image/*"
                    help="الصيغ المسموح بها: JPG, PNG, GIF. الحد الأقصى: 10MB" :max-size="10" col="col-12" />
                <x-slot:footer>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> حفظ التغييرات
                    </button>
                </x-slot:footer>
            </form>
        </x-bootstrap.modal>
    @endif
@endsection
