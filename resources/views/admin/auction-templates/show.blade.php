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
                <i class="ti ti-file-description me-2 text-primary"></i>{{ $template->name }}
            </h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-label-{{ $template->type->color() }}">
                    {{ $template->type->label() }}
                </span>
                @if($template->is_active)
                    <span class="badge bg-label-success">نشط ومتاح للمستأجرين</span>
                @else
                    <span class="badge bg-label-danger">معطل</span>
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
                        <span class="avatar-initial rounded-circle bg-label-{{ $template->type->color() }} d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <i class="ti ti-file-description fs-1"></i>
                        </span>
                    </div>

                    <h5 class="mb-1 fw-bold">{{ $template->name }}</h5>
                    <span class="badge bg-label-{{ $template->type->color() }} mb-3">
                        {{ $template->type->label() }}
                    </span>

                    {{-- Horizontal key metrics strip --}}
                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0 font-monospace text-primary">{{ count($template->field_names ?? []) }}</h5>
                            <small class="text-muted">حقول مستخرجة</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-{{ $template->is_active ? 'success' : 'danger' }} fw-bold">
                                {{ $template->is_active ? 'نشط' : 'معطل' }}
                            </h5>
                            <small class="text-muted">الحالة</small>
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
                            <li class="d-flex align-items-center justify-content-between py-2">
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
                    </ul>
                </div>
            </div>
        </div>

        {{-- Right column: Approved Specifications & Preview --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- Specifications Card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-ruler-measure me-2 text-primary"></i>المواصفات والمعايير المعتمدة لهذا القالب
                    </h5>
                    <span class="badge bg-label-{{ $template->type->color() }}">
                        {{ $template->type->label() }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small mb-1">المقاس والأبعاد القياسية:</span>
                                @if($template->type->value === 'brochure')
                                    <h6 class="mb-0 fw-bold text-primary"><i class="ti ti-dimensions me-1"></i>A4 رأسي (210 × 297 مم)</h6>
                                    <small class="text-muted">كتيّب متعدد الصفحات قابل للطباعة</small>
                                @elseif($template->type->value === 'social')
                                    <h6 class="mb-0 fw-bold text-info"><i class="ti ti-aspect-ratio me-1"></i>مربع 1:1 (1080 × 1080 بكسل)</h6>
                                    <small class="text-muted">منشور منصات التواصل الاجتماعي</small>
                                @else
                                    <h6 class="mb-0 fw-bold text-warning"><i class="ti ti-device-tv me-1"></i>عرضي Landscape 16:9</h6>
                                    <small class="text-muted">لوحات الطرق والشاشات الإعلانية</small>
                                @endif
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small mb-1">صيغة الملف المدعومة:</span>
                                <h6 class="mb-0 fw-bold text-heading">
                                    <i class="ti ti-file-code me-1"></i>{{ str_ends_with(strtolower($template->file_path), '.pdf') ? 'مستند PDF رقمي' : 'صورة تصميم عالية الدقة' }}
                                </h6>
                                <small class="text-muted">{{ strtoupper(pathinfo($template->file_path, PATHINFO_EXTENSION)) }} Format</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-primary mb-0 d-flex align-items-center" role="alert">
                                <i class="ti ti-info-circle fs-4 me-2"></i>
                                <div>
                                    هذا القالب يمثل طبقة التصميم الأساسية (Background Artboard). يقوم النظام آلياً بحقن بيانات المزاد، شعار المنشأة، الباركود الذكي QR، وتفاصيل وصور العقارات بدقة 100% دون أي تدخل يدوي من المستأجر.
                                </div>
                            </div>
                        </div>

                        @if(!empty($template->field_names))
                            <div class="col-12">
                                <span class="text-muted small d-block mb-1">الحقول المدعومة برمجياً:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($template->field_names as $fieldName)
                                        <code class="badge bg-label-secondary font-monospace">{{ $fieldName }}</code>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Live Design Preview Card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-eye me-2 text-primary"></i>معاينة تصميم القالب المعتمد
                    </h5>
                    @if($template->file_url)
                        <a href="{{ $template->file_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-external-link me-1"></i>فتح في نافذة جديدة
                        </a>
                    @endif
                </div>
                <div class="card-body text-center p-3 bg-light rounded-bottom">
                    @php
                        $ext = strtolower(pathinfo($template->file_path, PATHINFO_EXTENSION));
                    @endphp
                    @if(in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true))
                        <div class="p-2 bg-white rounded shadow-sm d-inline-block" style="max-width: 100%;">
                            <img src="{{ $template->file_url }}" alt="{{ $template->name }}" class="img-fluid rounded" style="max-height: 480px; object-fit: contain;">
                        </div>
                    @elseif($ext === 'pdf')
                        <div class="ratio ratio-16x9 bg-white rounded shadow-sm overflow-hidden" style="min-height: 420px;">
                            <iframe src="{{ $template->file_url }}#toolbar=0" title="{{ $template->name }}" style="border: none; width: 100%; height: 100%;"></iframe>
                        </div>
                    @else
                        <div class="py-5 text-muted">
                            <i class="ti ti-file-unknown fs-1 d-block mb-2"></i>
                            لا تتوفر معاينة مباشرة لهذا الملف. يمكنك تحميله للمراجعة.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
