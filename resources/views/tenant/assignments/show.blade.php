@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل الإسناد - ' . $assignment->name)

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ tenant_route_name('assignments.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('assignments.edit')
                <a href="{{ tenant_route_name('assignments.edit', $assignment->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الإسناد
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات الإسناد (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <img src="{{ $assignment->assignmentImage ? tasset($assignment->assignmentImage->file_path) : asset('static/brand/logos/logo.svg') }}"
                            alt="{{ $assignment->name }}" class="rounded-circle border shadow-sm"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $assignment->name }}</h4>
                    <p class="mb-2 text-muted fw-medium">{{ $assignment->assignment_number }}</p>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        <span class="badge bg-label-primary fs-6">{{ $assignment->category_label }}</span>
                        <span class="badge bg-label-{{ $assignment->is_active ? 'success' : 'danger' }} fs-6">
                            {{ $assignment->is_active ? 'نشط' : 'متوقف' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $assignment->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ $assignment->auctions->count() }}</h5>
                            <small class="text-muted">المزادات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-hash text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">رقم الإسناد:</span>
                                <span>{{ $assignment->assignment_number }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الجهة المسندة:</span>
                                <span>{{ $assignment->issuing_entity }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-map-pin text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المدينة:</span>
                                <span>{{ $assignment->city ?? '-' }}</span>
                            </li>
                            @if ($assignment->district)
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="ti ti-map text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">النطاق/الحي:</span>
                                    <span>{{ $assignment->district }}</span>
                                </li>
                            @endif
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-plus text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ البداية:</span>
                                <span>@date($assignment->start_date)</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-calendar-due text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الانتهاء:</span>
                                <span>@date($assignment->end_date)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            @php
                $assignmentDocuments = $assignment->attachments->where(
                    'category',
                    '!=',
                    \App\Enums\Tenants\Assignments\AssignmentAttachmentCategory::IMAGE->value,
                );
                $knownCategories = [
                    \App\Enums\Tenants\Assignments\AssignmentAttachmentCategory::LETTER->value => ['label' => 'خطاب الإسناد', 'icon' => 'ti-mail'],
                    \App\Enums\Tenants\Assignments\AssignmentAttachmentCategory::GENERAL_DOCUMENT->value => ['label' => 'المستندات العامة', 'icon' => 'ti-files'],
                    \App\Enums\Tenants\Assignments\AssignmentAttachmentCategory::ADDITIONAL_ATTACHMENT->value => ['label' => 'مرفقات إضافية', 'icon' => 'ti-paperclip'],
                ];
                $assignmentFileGroups = collect($knownCategories)
                    ->map(fn (array $meta, string $category) => [
                        'label' => $meta['label'],
                        'icon' => $meta['icon'],
                        'items' => $assignmentDocuments->where('category', $category)->values(),
                    ])
                    ->values()
                    ->all();
                $otherDocs = $assignmentDocuments->whereNotIn('category', array_keys($knownCategories))->values();
                if ($otherDocs->isNotEmpty()) {
                    $assignmentFileGroups[] = ['label' => 'مرفقات أخرى', 'icon' => 'ti-folder', 'items' => $otherDocs];
                }
            @endphp
        </div>

        {{-- تفاصيل العقارات والمزادات (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات التابعة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building-estate text-primary me-2"></i>العقارات المسجلة ضمن هذا الإسناد
                        ({{ $assignment->properties->count() }})
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @if ($assignment->properties->count() > 0)
                            <span class="badge bg-label-primary">{{ $assignment->properties->count() }} عقار</span>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>رقم الأصل</th>
                                <th>اسم العقار</th>
                                <th>النوع</th>
                                <th>المساحة</th>
                                <th>حالة البيانات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignment->properties as $prop)
                                <tr>
                                    <td><code>{{ $prop->property_number }}</code></td>
                                    <td>
                                        <a href="{{ tenant_route_name('properties.show', $prop->id) }}"
                                            class="fw-medium text-body">
                                            {{ $prop->name }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $prop->type_label }}</span></td>
                                    <td>{{ number_format((float) $prop->area) }} م²</td>
                                    <td><span
                                            class="badge {{ $prop->data_status_badge_class }}">{{ $prop->data_status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لا توجد عقارات مضافة لهذا الإسناد
                                        حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- المزادات التابعة --}}
            <div class="card {{ $assignmentDocuments->isNotEmpty() ? 'mb-4' : '' }}">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-gavel text-primary me-2"></i>المزادات المرتبطة
                        ({{ $assignment->auctions->count() }})
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @if ($assignment->auctions->count() > 0)
                            <span class="badge bg-label-primary">{{ $assignment->auctions->count() }} مزاد</span>
                        @endif
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>اسم المزاد</th>
                                <th>تاريخ البداية</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignment->auctions as $auc)
                                <tr>
                                    <td>
                                        <a href="{{ tenant_route_name('auctions.show', $auc->id) }}"
                                            class="fw-medium text-body">
                                            {{ $auc->name }}
                                        </a>
                                    </td>
                                    <td>@date($auc->start_date)</td>
                                    <td><span class="badge bg-label-secondary">{{ $auc->type_label }}</span></td>
                                    <td><span
                                            class="badge bg-label-{{ $auc->is_active ? 'success' : 'danger' }}">{{ $auc->is_active ? 'نشط' : 'متوقف' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لم يتم إنشاء أي مزاد مرتبط بهذا
                                        الإسناد بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($assignmentDocuments->isNotEmpty())
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-paperclip text-primary me-2"></i>المستندات والمرفقات
                        </h5>
                        <span class="badge bg-label-primary">{{ $assignmentDocuments->count() }}</span>
                    </div>
                    <x-ui.file-groups :groups="$assignmentFileGroups" />
                </div>
            @endif
        </div>
    </div>
@endsection
