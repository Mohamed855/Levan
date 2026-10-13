<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;

class AssignmentPropertySelectionDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'assignment-properties-table';
    }

    protected function resource()
    {
        return Property::query();
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all-properties" aria-label="تحديد كل العقارات الظاهرة">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم العقار'],
            ['data' => 'type', 'name' => 'type', 'title' => 'نوع العقار'],
            ['data' => 'location', 'name' => 'city', 'title' => 'المدينة / الحي'],
            ['data' => 'area', 'name' => 'area', 'title' => 'المساحة'],
            ['data' => 'data_status', 'name' => 'data_status', 'title' => 'حالة البيانات'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function (Property $property): string {
                $image = $property->main_image ? tasset($property->main_image) : asset('assets/img/illustrations/page-pricing-standard.png');

                return '<input type="checkbox" class="row-checkbox property-selector" value="'.(int) $property->id.'" data-name="'.e($property->name).'" data-number="'.e($property->property_number).'" data-image="'.e($image).'" aria-label="اختيار '.e($property->name).'">';
            })
            ->addColumn('image', function (Property $property): string {
                $image = $property->main_image ? tasset($property->main_image) : null;

                return $this->renderTableImage($image, (string) $property->name);
            })
            ->editColumn('type', fn (Property $property): string => '<span class="badge bg-label-info">'.e($property->type_label).'</span>')
            ->addColumn('location', fn (Property $property): string => e($property->city.($property->district ? ' - '.$property->district : '')))
            ->editColumn('area', fn (Property $property): string => number_format((float) $property->area).' م²')
            ->editColumn('data_status', fn (Property $property): string => '<span class="badge '.$property->data_status_badge_class.'">'.e($property->data_status_label).'</span>')
            ->editColumn('status', fn (Property $property): string => '<span class="badge bg-secondary">'.e($property->status_label).'</span>');
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'type', 'data_status', 'status'];
    }

    protected function applyCustomFilters($query)
    {
        return $query
            ->when(request()->filled('type'), fn ($builder) => $builder->where('type', request('type')))
            ->when(request()->filled('city'), fn ($builder) => $builder->where('city', request('city')))
            ->when(request()->filled('data_status'), fn ($builder) => $builder->where('data_status', request('data_status')))
            ->when(request()->filled('status'), fn ($builder) => $builder->where('status', request('status')));
    }

    protected function filterableColumns(): array
    {
        return ['property_number', 'name', 'type', 'city', 'district'];
    }

    protected function parameters(): array
    {
        $parameters = parent::parameters();
        $parameters['responsive'] = false;
        $parameters['columnDefs'] = [$parameters['columnDefs'][0]];

        return $parameters;
    }
}
