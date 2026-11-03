<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Admin\AdminMangment\Users\AdminUserStoreRequest;
use App\Http\Requests\Shared\DashboardBroadcastAuthRequest;
use App\Http\Requests\Shared\Wizard\ValidateWizardStepRequest;
use App\Http\Requests\Shared\Wizard\StoreWizardDraftRequest;
use App\Http\Requests\Site\CompleteCheckoutPaymentRequest;
use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Site\SubscriptionCheckoutRequest;
use App\Http\Requests\Site\VerifyCheckoutOtpRequest;
use App\Http\Requests\Site\VerifyTrialOtpRequest;
use App\Http\Requests\Tenants\Auth\LoginRequest;
use App\Http\Requests\Tenants\Auth\ResetTenantPasswordRequest;
use App\Http\Requests\Tenants\IssuingEntities\StoreIssuingEntityRequest;
use App\Http\Requests\Tenants\IssuingEntities\UpdateIssuingEntityRequest;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\Settings\MainSettingsRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Tickets\TicketRequest;
use Tests\TestCase;

class FormRequestAttributesContractTest extends TestCase
{
    public function test_site_request_attributes_match_their_front_end_labels(): void
    {
        $this->assertAttributes(new SubscriptionCheckoutRequest, [
            'billing_email' => 'البريد الإلكتروني للإدارة',
            'coupon_code' => 'كود الخصم',
        ]);
        $this->assertAttributes(new CompleteCheckoutPaymentRequest, [
            'bank_id' => 'اختر البنك للتحويل',
        ]);
        $this->assertAttributes(new StoreContactRequest, [
            'customer_name' => 'الاسم',
            'customer_email' => 'البريد الإلكتروني',
            'message' => 'الرسالة أو الاستفسار',
        ]);
        $this->assertAttributes(new VerifyCheckoutOtpRequest, ['otp' => 'رمز التحقق']);
        $this->assertAttributes(new VerifyTrialOtpRequest, ['otp' => 'رمز التحقق']);
    }

    public function test_shared_transport_fields_use_the_approved_fallback_names(): void
    {
        $this->assertAttributes(new DashboardBroadcastAuthRequest, [
            'channel_name' => 'قناة البث',
            'socket_id' => 'معرّف الاتصال',
        ]);
        $this->assertAttributes(new ValidateWizardStepRequest, [
            'target_path' => 'مسار التحقق',
            'target_method' => 'طريقة التحقق',
            'fields' => 'بيانات الحقول',
            'fields.*' => 'بيانات الحقول',
        ]);
    }

    public function test_wizard_draft_upload_attributes_follow_the_resource_context(): void
    {
        $auctionDraft = new StoreWizardDraftRequest;
        $auctionDraft->merge(['resource_key' => 'tenant.auction-create']);
        $this->assertAttributes($auctionDraft, [
            'report_image' => 'صورة غلاف التقرير',
            'attachments.*' => 'ملفات إضافية',
        ]);

        $ticketDraft = new StoreWizardDraftRequest;
        $ticketDraft->merge(['resource_key' => 'tenant.ticket-create']);
        $this->assertAttributes($ticketDraft, ['attachments.*' => 'المرفقات']);
    }

    public function test_admin_request_attributes_match_their_front_end_labels(): void
    {
        $this->assertAttributes(new AdminUserStoreRequest, [
            'name' => 'الاسم الكامل',
        ]);
    }

    public function test_tenant_request_attributes_match_their_front_end_labels(): void
    {
        $this->assertAttributes(new LoginRequest, [
            'email' => 'البريد الإلكتروني أو رقم الجوال',
        ]);
        $this->assertAttributes(new TicketRequest, [
            'ticketing_sections_id' => 'تصنيف التذكرة',
            'attachments.*' => 'المرفقات',
        ]);
        $this->assertAttributes(new ResetTenantPasswordRequest, [
            'token' => 'رمز إعادة التعيين',
            'email' => 'البريد الإلكتروني',
        ]);
    }

    public function test_issuing_entity_attributes_match_their_form_labels(): void
    {
        $expected = [
            'name' => 'اسم الجهة المسندة',
            'is_active' => 'الحالة',
        ];

        $this->assertAttributes(new StoreIssuingEntityRequest, $expected);
        $this->assertAttributes(new UpdateIssuingEntityRequest, $expected);
    }

    public function test_tenant_settings_and_assignment_attributes_cover_visible_and_internal_fields(): void
    {
        $this->assertAttributes(new MainSettingsRequest, [
            'office_name' => 'إسم الشركة',
            'sms_api_endpoint' => 'رابط واجهة الرسائل',
            'support_emails.*' => 'عناوين بريد الدعم',
        ]);
        $expectedAssignmentAttributes = [
            'property_ids.*' => 'العقارات المرتبطة',
            'general_documents.*' => 'مستندات عامة',
            'additional_attachments.*' => 'مرفقات إضافية',
        ];

        $this->assertAttributes(new StoreAssignmentRequest, $expectedAssignmentAttributes);
        $this->assertAttributes(new UpdateAssignmentRequest, $expectedAssignmentAttributes);
    }

    public function test_property_and_auction_wildcard_attributes_match_their_form_groups(): void
    {
        $propertyAttributes = [
            'boundary_geojson' => 'حدود العقار',
            'boundary_geojson.coordinates' => 'حدود العقار',
            'boundary_geojson.coordinates.0' => 'حدود العقار',
            'boundaries.*.direction' => 'الجهة',
            'owners.*.owner_name' => 'اسم المالك',
            'units.*.unit_number' => 'رقم الوحدة',
            'property_images.*' => 'صور إضافية للمعرض',
            'additional_documents.*' => 'مستندات إضافية',
        ];
        $auctionAttributes = [
            'property_ids.*' => 'العقارات المرتبطة بالإسناد المختار',
            'cover_image' => 'صورة غلاف المزاد',
            'attachments.*' => 'ملفات إضافية',
        ];

        $this->assertAttributes(new StorePropertyRequest, $propertyAttributes);
        $this->assertAttributes(new UpdatePropertyRequest, $propertyAttributes);
        $this->assertAttributes(new StoreAuctionRequest, $auctionAttributes);
        $this->assertAttributes(new UpdateAuctionRequest, $auctionAttributes);
    }

    public function test_property_boundary_geojson_coordinates_validation_message_uses_arabic_attribute(): void
    {
        foreach ([new StorePropertyRequest, new UpdatePropertyRequest] as $request) {
            $validator = validator(
                ['boundary_geojson' => ['type' => 'Polygon', 'coordinates' => []]],
                $request->rules(),
                $request->messages(),
                $request->attributes(),
            );

            $this->assertTrue($validator->fails());
            $this->assertSame(
                ['حقل حدود العقار مطلوب.'],
                $validator->errors()->get('boundary_geojson.coordinates.0'),
            );
        }
    }

    /**
     * @param array<string, string> $expected
     */
    private function assertAttributes(object $request, array $expected): void
    {
        $attributes = $request->attributes();

        foreach ($expected as $field => $label) {
            $this->assertArrayHasKey($field, $attributes, sprintf('%s is missing %s.', $request::class, $field));
            $this->assertSame($label, $attributes[$field], sprintf('%s has an unexpected label for %s.', $request::class, $field));
        }
    }
}
