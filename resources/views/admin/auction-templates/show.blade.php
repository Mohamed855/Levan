@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل قالب المزاد')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-label-secondary" href="{{ route('admin.auction-templates.index') }}">
        <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
    </a>
    <a class="btn btn-primary" href="{{ route('admin.auction-templates.edit', $template) }}">
        <i class="ti ti-edit me-1"></i> تعديل القالب
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold text-primary">{{ $template->name }}</h5>
                <p class="text-muted">{{ $template->description ?: 'لا يوجد وصف مضاف.' }}</p>
                <dl class="row mb-0">
                    <dt class="col-5">النوع</dt>
                    <dd class="col-7">
                        <span class="badge bg-label-primary">{{ $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type }}</span>
                    </dd>
                    <dt class="col-5">الحالة العامة</dt>
                    <dd class="col-7">
                        <span class="badge {{ $template->status === 'published' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $template->status ?? 'draft' }}</span>
                    </dd>
                    <dt class="col-5">الإصدار الحالي</dt>
                    <dd class="col-7 font-monospace fw-bold">{{ $template->current_version ?: '—' }}</dd>
                    <dt class="col-5">مفعل في النظام</dt>
                    <dd class="col-7">{{ $template->is_active ? 'نعم' : 'لا' }}</dd>
                    <dt class="col-5">أضيف بواسطة</dt>
                    <dd class="col-7">{{ $template->creator?->name ?? 'النظام' }}</dd>
                    <dt class="col-5">تاريخ الإضافة</dt>
                    <dd class="col-7">{{ $template->created_at?->format('Y-m-d H:i') }}</dd>
                    @if(!empty($template->field_names))
                        <dt class="col-5">الحقول المعرفة</dt>
                        <dd class="col-7">
                            @foreach($template->field_names as $fieldName)
                                <span class="badge bg-label-info">{{ $fieldName }}</span>
                            @endforeach
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-versions me-2 text-primary"></i>إصدارات القالب</h5>
                <span class="badge bg-label-info">{{ $template->versions->count() }} إصدار</span>
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
                        @forelse($template->versions as $version)
                            <tr>
                                <td class="fw-bold font-monospace">إصدار {{ $version->version }}</td>
                                <td>
                                    <span class="badge {{ $version->status === 'published' ? 'bg-label-success' : 'bg-label-warning' }}">
                                        {{ $version->status === 'published' ? 'منشور ومتاح' : 'مسودة' }}
                                    </span>
                                </td>
                                <td>{{ $version->published_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                <td>{{ $version->creator?->name ?? '—' }}</td>
                                <td>
                                    @if($version->status !== 'published')
                                        <form method="POST" action="{{ route('admin.auction-templates.versions.publish', [$template, $version]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="ti ti-check me-1"></i> نشر الإصدار
                                            </button>
                                        </form>
                                    @else
                                        <span class="badge bg-label-secondary">معتمد كإصدار نشط</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">لا توجد إصدارات مسجلة لهذا القالب.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
