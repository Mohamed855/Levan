<?php

declare(strict_types=1);

namespace App\Services\Admin\AuctionTemplates;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use Illuminate\Support\Facades\Storage;

final class AuctionTemplateService
{
    public function getStatistics(): array
    {
        $counts = AuctionTemplate::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active")
            ->selectRaw("SUM(CASE WHEN type = 'brochure' THEN 1 ELSE 0 END) as brochure")
            ->selectRaw("SUM(CASE WHEN type = 'billboard' THEN 1 ELSE 0 END) as billboard")
            ->selectRaw("SUM(CASE WHEN type = 'social' THEN 1 ELSE 0 END) as social")
            ->selectRaw("SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive")
            ->first();

        return [
            'total'     => (int) ($counts->total ?? 0),
            'active'    => (int) ($counts->active ?? 0),
            'brochure'  => (int) ($counts->brochure ?? 0),
            'billboard' => (int) ($counts->billboard ?? 0),
            'social'    => (int) ($counts->social ?? 0),
            'inactive'  => (int) ($counts->inactive ?? 0),
        ];
    }

    public function toggleStatus(AuctionTemplate $template): bool
    {
        $newStatus = ! $template->is_active;
        return $template->update(['is_active' => $newStatus]);
    }

    public function delete(AuctionTemplate $template): bool
    {
        return (bool) $template->delete();
    }

    /**
     * Standard reference guide of form fields for each template type.
     */
    public function getFieldGuide(): array
    {
        return [
            'brochure' => [
                'title' => 'قالب البروشور',
                'fields' => [
                    ['name' => 'auction_name', 'label' => 'اسم المزاد', 'required' => true],
                    ['name' => 'auction_date', 'label' => 'تاريخ المزاد (Y/m/d)', 'required' => true],
                    ['name' => 'auction_time', 'label' => 'وقت المزاد', 'required' => false],
                    ['name' => 'auction_city', 'label' => 'المدينة', 'required' => true],
                    ['name' => 'auction_type', 'label' => 'نوع المزاد', 'required' => false],
                    ['name' => 'auction_category', 'label' => 'تصنيف المزاد', 'required' => false],
                    ['name' => 'platform_name', 'label' => 'منصة المزاد', 'required' => false],
                    ['name' => 'platform_url', 'label' => 'رابط المنصة', 'required' => false],
                    ['name' => 'supervising_entity', 'label' => 'الجهة المشرفة', 'required' => false],
                    ['name' => 'properties_count', 'label' => 'عدد العقارات', 'required' => false],
                    ['name' => 'assignment_number', 'label' => 'رقم الإسناد', 'required' => false],
                    ['name' => 'issuing_entity', 'label' => 'الجهة المسندة', 'required' => false],
                    ['name' => 'tenant_name', 'label' => 'اسم الشركة / المنشأة', 'required' => true],
                    ['name' => 'description', 'label' => 'وصف المزاد', 'required' => false],
                    ['name' => 'notes', 'label' => 'الملاحظات', 'required' => false],
                ],
            ],
            'billboard' => [
                'title' => 'قالب اللوحة الإعلانية',
                'fields' => [
                    ['name' => 'auction_name', 'label' => 'اسم المزاد', 'required' => true],
                    ['name' => 'auction_date', 'label' => 'تاريخ المزاد (Y/m/d)', 'required' => true],
                    ['name' => 'auction_time', 'label' => 'وقت المزاد', 'required' => false],
                    ['name' => 'auction_city', 'label' => 'المدينة', 'required' => true],
                    ['name' => 'platform_name', 'label' => 'منصة المزاد', 'required' => false],
                    ['name' => 'supervising_entity', 'label' => 'الجهة المشرفة', 'required' => false],
                    ['name' => 'properties_count', 'label' => 'عدد العقارات', 'required' => false],
                    ['name' => 'tenant_name', 'label' => 'اسم الشركة / المنشأة', 'required' => true],
                ],
            ],
            'social' => [
                'title' => 'قالب السوشيال ميديا',
                'fields' => [
                    ['name' => 'auction_name', 'label' => 'اسم المزاد', 'required' => true],
                    ['name' => 'auction_date', 'label' => 'تاريخ المزاد (Y/m/d)', 'required' => true],
                    ['name' => 'auction_city', 'label' => 'المدينة', 'required' => true],
                    ['name' => 'platform_name', 'label' => 'منصة المزاد', 'required' => false],
                    ['name' => 'properties_count', 'label' => 'عدد العقارات', 'required' => false],
                    ['name' => 'tenant_name', 'label' => 'اسم الشركة / المنشأة', 'required' => true],
                ],
            ],
        ];
    }
}
