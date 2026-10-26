@extends('admin.layouts.layoutMaster')
@section('title', 'الأسئلة الشائعة')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">الأسئلة الشائعة</h5>
            @can('faqs.create')
                <a class="btn btn-primary" href="{{ route('admin.faqs.create') }}">
                    <i class="ti ti-plus me-1"></i> إضافة سؤال
                </a>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>السؤال</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faqs as $faq)
                        <tr>
                            <td>{{ $faq->question }}</td>
                            <td>
                                @if ($faq->is_active)
                                    <span class="badge bg-label-success">مفعل</span>
                                @else
                                    <span class="badge bg-label-danger">معطل</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    @can('faqs.edit')
                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-icon btn-label-primary" title="تعديل">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    @endcan
                                    @can('faqs.delete')
                                        <x-actions.delete :id="$faq->id" :url="route('admin.faqs.destroy', $faq)" permission="faqs.delete" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">لا توجد أسئلة</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $faqs->links() }}
        </div>
    </div>
</div>
@endsection
