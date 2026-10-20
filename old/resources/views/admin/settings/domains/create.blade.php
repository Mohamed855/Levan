@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة امتداد دومين')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">إضافة امتداد دومين جديد</h5>
                    <a href="{{ route('admin.settings.domains.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.domains.store') }}" method="POST" data-form-validate
                        data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                        data-draft-resource-key="admin.domain-create">
                        @csrf
                        <div class="row g-4">
                            <x-form.controls.input name="domain_extension" id="domain_extension" label="الامتداد"
                                placeholder=".com" required col="col-md-6" />

                            <x-form.controls.input type="number" step="0.01" name="price" id="price" label="السعر"
                                placeholder="2000" required col="col-md-6" />
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <x-form.wizard.draft-button />
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>
                                حفظ
                            </button>
                            <a href="{{ route('admin.settings.domains.index') }}" class="btn btn-secondary">
                                <i class="ti ti-x me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
