@extends('layouts.dashboard')

@section('title', 'سجل العمليات')
@section('dashboard-scope', 'tenant')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('content')
    <x-data-table.page table-id="tenant-activity-log-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي العمليات"
                :value="$stats['total']"
                subtitle="كافة الأحداث المسجلة"
                icon="ti-history"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات الإنشاء"
                :value="$stats['created']"
                subtitle="سجلات أُضيفت حديثاً"
                icon="ti-plus"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات التعديل"
                :value="$stats['updated']"
                subtitle="تحديثات تمت على البيانات"
                icon="ti-edit"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات الحذف"
                :value="$stats['deleted']"
                subtitle="سجلات تم حذفها"
                icon="ti-trash"
                color="danger"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات اليوم"
                :value="$stats['today']"
                subtitle="أنشطة تمت خلال اليوم"
                icon="ti-calendar-time"
                color="warning"
            />
        </x-slot:stats>

        <div class="card">
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-hover table-striped w-100', 'id' => 'tenant-activity-log-table']) !!}
            </div>
        </div>
    </x-data-table.page>
@endsection
