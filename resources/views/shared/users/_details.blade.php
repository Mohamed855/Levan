@php
    $isActive = ($user->status?->value ?? $user->status) === 'active';
@endphp

<div class="row">
    {{-- بطاقة معلومات المستخدم (العمود الأيمن) --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mx-auto mb-3 position-relative" style="width: 100px; height: 100px;">
                    <img src="{{ $avatarUrl }}" alt="صورة المستخدم"
                        class="rounded-circle border shadow-sm w-100 h-100 object-fit-cover"
                        onerror="this.onerror=null;this.src='{{ asset('static/brand/logos/logo.svg') }}';">
                    @if ($canEditAvatar)
                        <button type="button"
                            class="btn btn-sm btn-icon btn-primary rounded-circle position-absolute bottom-0 end-0"
                            data-bs-toggle="modal" data-bs-target="#changeProfileImageModal"
                            title="تغيير الصورة الشخصية">
                            <i class="ti ti-edit ti-14px"></i>
                        </button>
                    @endif
                </div>

                <h4 class="mb-1 fw-bold text-primary">{{ $user->name }}</h4>
                <p class="mb-2 text-muted fw-medium">{{ $user->email }}</p>

                <div class="d-flex justify-content-center flex-wrap gap-1 mb-3">
                    <span class="badge bg-label-{{ $isActive ? 'success' : 'danger' }} fs-6">
                        {{ $isActive ? 'نشط' : 'غير نشط' }}
                    </span>
                    @if (!empty($isAdminRole))
                        <span class="badge bg-label-info fs-6">مدير النظام</span>
                    @endif
                    @forelse ($user->roles as $roleItem)
                        <span class="badge bg-label-primary fs-6">
                            {{ \App\Support\AccessLabels::role($roleItem->name) }}
                        </span>
                    @empty
                        <span class="badge bg-label-secondary fs-6">بدون دور</span>
                    @endforelse
                </div>

                <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                    <div>
                        <h6 class="mb-0 fw-bold">
                            {{ optional($user->created_at)?->locale('ar')->translatedFormat('d F Y') ?? '-' }}</h6>
                        <small class="text-muted">تاريخ التسجيل</small>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold">
                            {{ $user->last_login_at ? $user->last_login_at->locale('ar')->diffForHumans() : 'لم يسجل' }}
                        </h6>
                        <small class="text-muted">آخر دخول</small>
                    </div>
                </div>

                <div class="info-container text-start">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-user text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الاسم:</span>
                            <span>{{ $user->name }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-mail text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">البريد:</span>
                            <a href="mailto:{{ $user->email }}" class="text-truncate">{{ $user->email }}</a>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-phone text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الهاتف:</span>
                            <span dir="ltr">{{ $user->phone ?? 'غير محدد' }}</span>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="ti ti-shield-check text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الحالة:</span>
                            <span class="badge bg-label-{{ $isActive ? 'success' : 'danger' }}">
                                {{ $isActive ? 'نشط' : 'غير نشط' }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- سجل النشاطات (العمود الأيسر) --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-history text-primary me-2"></i>سجل النشاطات
                </h5>
                <span class="badge bg-label-primary">آخر النشاطات</span>
            </div>
            <div class="card-body">
                @if ($recentActivities->isNotEmpty())
                    <ul class="timeline mt-2 mb-0">
                        @foreach ($recentActivities as $activity)
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-1">
                                        <h6 class="mb-0 text-capitalize">{{ $activity->description }}</h6>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-history-off fs-2 d-block mb-2"></i>
                        لا توجد نشاطات مسجلة حتى الآن.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($canEditAvatar)
    <x-bootstrap.modal id="changeProfileImageModal">
        <form id="changeProfileImageForm" method="POST" enctype="multipart/form-data" novalidate data-form-validate
            action="{{ $uploadAction }}" data-validation-url="{{ $validationUrl }}">
            @csrf
            <x-form.controls.file name="profile_image" id="profile_image" label="اختر صورة شخصية جديدة"
                :value="$existingImageValue" accept="image/*" help="الصيغ المسموح بها: JPG, PNG, GIF. الحد الأقصى: 10MB"
                :max-size="10" col="col-12" />
            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>إلغاء
                </button>
                <button type="submit" form="changeProfileImageForm" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> حفظ التغييرات
                </button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>
@endif

