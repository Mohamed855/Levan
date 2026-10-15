@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل العميل')


@section('content')
    <div class="client-header">
        <h4>تفاصيل العميل</h4>
        <div class="action-buttons">
            <a href="{{ route('admin.content_management.client.edit', $client->id) }}" class="btn btn-primary">
                <i class="ti ti-edit me-1"></i> تعديل
            </a>
            <a href="{{ route('admin.content_management.client.index') }}" class="btn-back">
                <i class="ti ti-arrow-back me-1"></i> رجوع
            </a>
        </div>
    </div>

    <div class="client-info-card">
        <div class="client-details-grid">
            <div class="detail-item">
                <span class="detail-label">اسم العميل</span>
                <span class="detail-value">{{ $client->name }}</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">الحالة</span>
                <span class="status-badge {{ $client->status ? 'active' : 'inactive' }}">
                    {{ $client->status ? 'نشط' : 'غير نشط' }}
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">تاريخ الإنشاء</span>
                <span class="detail-value">{{ $client->created_at ? $client->created_at->format('Y-m-d H:i') : '-' }}</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">آخر تحديث</span>
                <span class="detail-value">{{ $client->updated_at ? $client->updated_at->format('Y-m-d H:i') : '-' }}</span>
            </div>
        </div>

        @if ($client->logo)
            <div class="detail-item logo-container" style="margin-top: 1.25rem;">
                <span class="detail-label">الشعار</span>
                <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}" class="logo-img">
            </div>
        @endif
    </div>
@endsection
