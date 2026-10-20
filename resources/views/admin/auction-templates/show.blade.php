@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل قالب المزاد: ' . $template->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    {{-- Page Header Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti {{ $template->type->icon() }} me-2 text-primary"></i>{{ $template->name }}
            </h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-label-{{ $template->type->color() }}">
                    <i class="ti {{ $template->type->icon() }} me-1"></i>{{ $template->type->label() }}
                </span>
                @if($template->is_active)
                    <span class="badge bg-label-success"><i class="ti ti-check me-1"></i>نشط ومتاح للمستأجرين</span>
                @else
                    <span class="badge bg-label-danger"><i class="ti ti-ban me-1"></i>معطل</span>
                @endif
                <span class="badge bg-label-secondary font-monospace">ID: {{ $template->id }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('auction_templates.edit')
                <a href="{{ route('admin.auction-templates.edit', $template) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i>تعديل القالب
                </a>
            @endcan
            <a href="{{ route('admin.auction-templates.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i>العودة للقائمة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Left column: Entity Profile Card & Attachments Card --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            {{-- Entity Profile Card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-body text-center pt-4">
                    <div class="mx-auto mb-3">
                        @if($template->thumbnail_url)
                            <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="rounded-circle shadow-sm" style="width: 100px; height: 100px; object-fit: cover;">
                        @else
                            <span class="avatar-initial rounded-circle bg-label-{{ $template->type->color() }} d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <i class="ti {{ $template->type->icon() }} fs-1"></i>
                            </span>
                        @endif
                    </div>

                    <h5 class="mb-1 fw-bold">{{ $template->name }}</h5>
                    <span class="badge bg-label-{{ $template->type->color() }} mb-3">
                        <i class="ti {{ $template->type->icon() }} me-1"></i>{{ $template->type->label() }}
                    </span>

                    {{-- Horizontal key metrics strip --}}
                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0 font-monospace text-primary">{{ count($template->field_names ?? []) }}</h5>
                            <small class="text-muted">حقول مستخرجة</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-{{ $template->is_active ? 'success' : 'danger' }}">
                                <i class="ti {{ $template->is_active ? 'ti-check' : 'ti-ban' }}"></i>
                            </h5>
                            <small class="text-muted">{{ $template->is_active ? 'نشط' : 'معطل' }}</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-calendar text-muted me-2"></i>
                                <span class="fw-bold me-2">تاريخ الإنشاء:</span>
                                <span>{{ $template->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-user text-muted me-2"></i>
                                <span class="fw-bold me-2">بواسطة:</span>
                                <span>{{ $template->creator?->name ?? 'المسؤول' }}</span>
                            </li>
                            @if($template->description)
                                <li class="mt-3 pt-2 border-top">
                                    <span class="fw-bold d-block mb-1 text-heading">الوصف والملاحظات:</span>
                                    <p class="text-muted small mb-0">{{ $template->description }}</p>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Documents & Attachments Card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header pb-2">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="ti ti-paperclip me-1 text-primary"></i>ملف القالب والمستندات
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        @if($template->file_url)
                            @php
                                $isPdf = str_ends_with(strtolower($template->file_path), '.pdf');
                            @endphp
                            <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                    <i class="ti {{ $isPdf ? 'ti-file-type-pdf text-danger' : 'ti-photo text-primary' }} fs-4"></i>
                                    <div>
                                        <span class="text-truncate fw-medium d-block">{{ $isPdf ? 'ملف مستند PDF' : 'ملف صورة التصميم' }}</span>
                                        <small class="text-muted font-monospace">{{ $isPdf ? 'PDF Template' : 'Design Image' }}</small>
                                    </div>
                                </div>
                                <a href="{{ $template->file_url }}" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="معاينة وتحميل الملف">
                                    <i class="ti ti-download"></i>
                                </a>
                            </li>
                        @endif

                        @if($template->thumbnail_url)
                            <li class="d-flex align-items-center justify-content-between py-2">
                                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                    <i class="ti ti-photo text-info fs-4"></i>
                                    <div>
                                        <span class="text-truncate fw-medium d-block">صورة المعاينة</span>
                                        <small class="text-muted">Thumbnail Image</small>
                                    </div>
                                </div>
                                <a href="{{ $template->thumbnail_url }}" target="_blank" class="btn btn-sm btn-icon btn-label-info" title="عرض الصورة">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right column: Fields and mapping comparison table --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list-check me-2 text-primary"></i>مطابقة حقول القالب مع النظام
                    </h5>
                    <span class="badge bg-label-info">{{ $template->type->label() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered w-100 mb-0">
                        <thead>
                            <tr>
                                <th>اسم الحقل البرمجي</th>
                                <th>البيان المقابل في المزاد</th>
                                <th class="text-center">الأهمية</th>
                                <th class="text-center">حالة الاعتماد في القالب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $expected = $typeGuide['fields'] ?? [];
                                $extracted = $template->field_names ?? [];
                            @endphp
                            @forelse($expected as $field)
                                @php
                                    $found = in_array($field['name'], $extracted, true);
                                @endphp
                                <tr>
                                    <td><code class="text-primary font-monospace">{{ $field['name'] }}</code></td>
                                    <td>{{ $field['label'] }}</td>
                                    <td class="text-center">
                                        @if($field['required'])
                                            <span class="badge bg-label-warning">إلزامي</span>
                                        @else
                                            <span class="badge bg-label-info">اختياري</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($found)
                                            <span class="badge bg-label-success"><i class="ti ti-check me-1"></i>متطابق وموجود</span>
                                        @else
                                            @if($field['required'])
                                                <span class="badge bg-label-danger"><i class="ti ti-alert-triangle me-1"></i>مطلوب وغير موجود</span>
                                            @else
                                                <span class="badge bg-label-secondary">غير موجود</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">لا توجد حقول قياسية محددة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @php
                    $unexpected = array_diff($extracted, array_column($expected, 'name'));
                @endphp
                @if(count($unexpected) > 0)
                    <div class="card-body border-top">
                        <h6 class="fw-bold text-heading mb-2">
                            <i class="ti ti-info-circle me-1 text-info"></i>حقول إضافية وُجدت في الـ PDF غير محددة بالنموذج القياسي:
                        </h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($unexpected as $unField)
                                <span class="badge bg-label-dark font-monospace">{{ $unField }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
