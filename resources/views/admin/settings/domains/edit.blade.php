@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل امتداد دومين')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">تعديل امتداد دومين</h5>
                    <a href="{{ route('admin.settings.domains.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.domains.update', $domain->id) }}" method="POST"
                        data-form-validate>
                        @csrf
                        @method('PUT')
                        <div class="row g-4">
                            <x-form.controls.input name="domain_extension" id="domain_extension" label="الامتداد"
                                :value="old('domain_extension', $domain->domain_extension)" required col="col-md-6" />

                            <x-form.controls.input type="number" step="0.01" name="price" id="price"
                                label="السعر" :value="old('price', $domain->price)" required col="col-md-6" />
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i>
                                تعديل
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
