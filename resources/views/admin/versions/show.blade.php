@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الإصدارة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-header py-4">

                    <h6 class="card-title mb-0">
                        <i class="ti ti-git-pull-request-draft text-warning me-2"></i>
                        تفاصيل الإصدارة رقم {{ $version->version }}
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body px-5">
                    <div class="table-responsive">
                        <table class="table table-striped  small text-center">
                            <tr>
                                <td class="fw-bold">رقم الإصدارة </td>
                                <td> {{ $version->version }}</td>
                            </tr>

                            <tr>
                                <td class="fw-bold">المرحلة </td>
                                <td>
                                    <span
                                        class="badge bg-{{ $version->stage->color() }}">{{ $version->stage->label() }}</span>
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">الحالة </td>
                                <td>
                                    @if ($version->is_active)
                                        <span class="badge bg-success">مفعلة</span>
                                    @else
                                        <span class="badge bg-danger">غير مفعلة</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">تاريخ الإصدار </td>
                                <td>
                                    {{ $version->released_at ? $version->released_at->format('Y-m-d') : '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">مسار الإصدارة </td>
                                <td>
                                    {{ $version->file_path }}
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold text-danger">عدد المستخدمين للاصدارة</td>
                                <td>
                                    {{ $version->tenants->count() }}
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">سجل التغييرات </td>
                                <td>
                                    {{ $version->changelog ?? '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">تم الإنشاء بواسطة </td>
                                <td>
                                    {{ optional($version->creator)->name ?? '-' }}
                                </td>
                            </tr>

                            <tr>
                                <td class="fw-bold">آخر تحديث بواسطة </td>
                                <td>
                                    {{ optional($version->updater)->name ?? '-' }}
                                </td>
                            </tr>

                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
