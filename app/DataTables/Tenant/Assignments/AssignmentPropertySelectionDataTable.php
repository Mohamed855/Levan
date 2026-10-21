<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Assignments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenant\Property;

class AssignmentPropertySelectionDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'assignment-properties-table';
    }

    protected function resource()
    {
        return Property::query()->whereNull('assignment_id');
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
                $fallbackImage = asset('static/brand/logos/logo.svg');
                $image = $property->main_image ? tasset($property->main_image) : $fallbackImage;

                return '<input type="checkbox" class="row-checkbox property-selector" value="'.(int) $property->id.'" data-name="'.e($property->name).'" data-number="'.e($property->property_number).'" data-type="'.e($property->type_label).'" data-city="'.e($property->city).'" data-image="'.e($image).'" aria-label="اختيار '.e($property->name).'">';
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

    protected function buttons(): array
    {
        return [];
    }
}
