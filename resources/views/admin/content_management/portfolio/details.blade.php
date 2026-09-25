@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل المحفظة')

@section('vendor-style')
@endsection

@section('page-style')
<style>
    .portfolio-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .portfolio-info-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }

    .portfolio-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .detail-value {
        font-weight: 600;
        color: #372632;
        font-size: 1rem;
    }

    .logo-container {
        text-align: center;
    }

    .logo-img {
        max-width: 200px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .status-badge.active {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .status-badge.inactive {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-back {
        background: #6c757d;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-back:hover {
        background: #5a6268;
        color: white;
    }
</style>
@endsection

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
