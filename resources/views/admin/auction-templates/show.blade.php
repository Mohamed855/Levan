@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل قالب المزاد - ' . $template->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-layout-dashboard me-2 text-primary"></i>تفاصيل قالب المزاد: {{ $template->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $template->status === 'published' ? 'bg-success' : 'bg-warning' }}">
                    {{ $template->status === 'published' ? 'منشور ومتاح' : $template->status ?? 'مسودة' }}
                </span>
                <span class="badge bg-primary">
                    {{ $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type }}
                </span>
                <span class="badge bg-label-dark text-dark">إصدار
                    {{ $template->current_version ?: '1.0' }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('auction_templates.edit')
                <a href="{{ route('admin.auction-templates.edit', $template) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل القالب
                </a>
            @endcan
            <a href="{{ route('admin.auction-templates.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات القالب (العمود الأيسر) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-primary rounded"
                        style="width: 100px; height: 100px;">
                        <i class="ti ti-template text-white" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="mb-1">{{ $template->name }}</h5>
                    <span class="badge bg-primary mb-3">
                        {{ $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type }}
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0 text-primary">{{ $template->versions->count() }}</h5>
                            <small class="text-muted">الإصدارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-info">
                                {{ is_array($template->field_names) ? count($template->field_names) : 0 }}</h5>
                            <small class="text-muted">الحقول المعرفة</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            @if ($template->description)
                                <li class="mb-2">
                                    <span class="fw-bold me-1">الوصف:</span>
                                    <span class="text-muted small">{{ $template->description }}</span>
                                </li>
                            @endif
                            <li class="mb-2">
                                <span class="fw-bold me-1">الحالة العامة:</span>
                                <span class="badge {{ $template->status === 'published' ? 'bg-success' : 'bg-warning' }}">
                                    {{ $template->status ?? 'draft' }}
                                </span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">الإصدار المعتمد:</span>
                                <span class="fw-bold">{{ $template->current_version ?: '—' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">مفعل في النظام:</span>
                                <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $template->is_active ? 'نعم' : 'لا' }}
                                </span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">أضيف بواسطة:</span>
                                <span>{{ $template->creator?->name ?? 'النظام' }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="fw-bold me-1">تاريخ الإضافة:</span>
                                <span>{{ $template->created_at?->format('Y-m-d H:i') ?? '-' }}</span>
                            </li>
                            @if (!empty($template->field_names))
                                <li class="mb-0 border-top pt-2">
                                    <span class="fw-bold d-block mb-2">الحقول المعرفة:</span>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($template->field_names as $fieldName)
                                            <span class="badge bg-info">{{ $fieldName }}</span>
                                        @endforeach
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- الجداول المرتبطة (العمود الأيمن) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-versions me-2 text-primary"></i>إصدارات القالب ({{ $template->versions->count() }})
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الإصدار</th>
                                <th>الحالة</th>
                                <th>تاريخ النشر</th>
                                <th>بواسطة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($template->versions as $version)
                                <tr>
                                    <td class="fw-bold">إصدار {{ $version->version }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $version->status === 'published' ? 'bg-success' : 'bg-warning' }}">
                                            {{ $version->status === 'published' ? 'منشور ومتاح' : 'مسودة' }}
                                        </span>
                                    </td>
                                    <td>{{ $version->published_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td>{{ $version->creator?->name ?? '—' }}</td>
                                    <td>
                                        @if ($version->status !== 'published')
                                            <form method="POST"
                                                action="{{ route('admin.auction-templates.versions.publish', [$template, $version]) }}"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="ti ti-check me-1"></i> اعتماد الإصدار
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge bg-secondary">معتمد كإصدار نشط</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لا توجد إصدارات مسجلة لهذا القالب
                                        حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
