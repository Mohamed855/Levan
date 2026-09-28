@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل المحفظة')


@section('content')
<div class="portfolio-header">
    <h4>تفاصيل المحفظة</h4>
    <div class="action-buttons">
        <a href="{{ route('admin.content_management.portfolio.edit', $portfolio->id) }}" class="btn btn-primary">
            <i class="ti ti-edit me-1"></i> تعديل
        </a>
        <a href="{{ route('admin.content_management.portfolio.index') }}" class="btn-back">
            <i class="ti ti-arrow-back me-1"></i> رجوع
        </a>
    </div>
</div>

<div class="portfolio-info-card">
    <div class="portfolio-details-grid">
        <div class="detail-item">
            <span class="detail-label">اسم المحفظة</span>
            <span class="detail-value">{{ $portfolio->name }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">الحالة</span>
            <span class="status-badge {{ $portfolio->status ? 'active' : 'inactive' }}">
                {{ $portfolio->status ? 'نشط' : 'غير نشط' }}
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label">تاريخ الإنشاء</span>
            <span class="detail-value">{{ $portfolio->created_at ? $portfolio->created_at->format('Y-m-d H:i') : '-' }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">آخر تحديث</span>
            <span class="detail-value">{{ $portfolio->updated_at ? $portfolio->updated_at->format('Y-m-d H:i') : '-' }}</span>
        </div>
    </div>

    @if($portfolio->logo)
    <div class="detail-item logo-container" style="margin-top: 20px;">
        <span class="detail-label">الشعار</span>
        <img src="{{ asset('storage/' . $portfolio->logo) }}" alt="{{ $portfolio->name }}" class="logo-img">
    </div>
    @endif
</div>
@endsection
