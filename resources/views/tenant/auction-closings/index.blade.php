@extends('tenant.layouts.layoutMaster')

@section('title', 'إقفال المزادات')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <x-data-table.page table-id="auction-closings-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي المزادات" :value="number_format($statistics['total'])" subtitle="كافة المزادات المسجلة" icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مزادات لم تُقفل" :value="number_format($statistics['not_started'])" subtitle="لم تسجل نتائجها بعد" icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إقفالات محفوظة" :value="number_format($statistics['saved'])" subtitle="تحتوي على عقارات غير مباعة" icon="ti-file-check" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مزادات منتهية" :value="number_format($statistics['ended'])" subtitle="تم بيع جميع عقاراتها" icon="ti-lock" color="success" />
        </x-slot:stats>
        <x-slot:filters>
            <x-data-table.filters table="#auction-closings-table">
                <x-filter.select id="closing-state-filter" filterKey="closing_status" label="حالة الإقفال" :options="['not_started' => 'لم يبدأ', 'saved' => 'محفوظ', 'ended' => 'منتهي']" />
                <x-filter.date id="closing-created-from" filterKey="created_from" label="إنشاء المزاد من" />
                <x-filter.date id="closing-created-to" filterKey="created_to" label="إنشاء المزاد إلى" />
            </x-data-table.filters>
        </x-slot:filters>
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-closings-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection