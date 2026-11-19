@extends('tenant.layouts.layoutMaster')

@section('title', 'بانتظار الاعتماد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-data-table.page table-id="pending-auctions-table">
        <x-slot:filters>
            <x-data-table.filters table="#pending-auctions-table">
                <x-filter.input id="pending-auction-name" filterKey="name" table="#pending-auctions-table" label="اسم المزاد" />
                <x-filter.date id="pending-created-from" filterKey="created_from" target="#pending-auctions-table"
                    label="تاريخ الإنشاء من" />
                <x-filter.date id="pending-created-to" filterKey="created_to" target="#pending-auctions-table"
                    label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
