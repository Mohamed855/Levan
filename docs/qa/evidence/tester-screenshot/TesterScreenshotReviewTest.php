<?php

declare(strict_types=1);

namespace Tests\Feature\QaReview;

use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/** Read-only contract checks. No database queries, users, migrations, or outbound mail. */
final class TesterScreenshotReviewTest extends TestCase
{
    public function test_tc010_create_accepts_absent_or_empty_administrative_region(): void
    {
        $rules = (new StorePropertyRequest())->rules();
        foreach ([[], ['administrative_region' => null], ['administrative_region' => ''], ['administrative_region' => 'منطقة الرياض']] as $data) {
            $this->assertTrue(Validator::make($data, ['administrative_region' => $rules['administrative_region']])->passes());
        }
        $this->assertTrue(Validator::make(['administrative_region' => str_repeat('x', 101)], ['administrative_region' => $rules['administrative_region']])->fails());
    }

    public function test_tc010_edit_accepts_absent_or_empty_administrative_region(): void
    {
        $rules = (new UpdatePropertyRequest())->rules();
        foreach ([[], ['administrative_region' => null], ['administrative_region' => ''], ['administrative_region' => 'منطقة الرياض']] as $data) {
            $this->assertTrue(Validator::make($data, ['administrative_region' => $rules['administrative_region']])->passes());
        }
        $this->assertTrue(Validator::make(['administrative_region' => str_repeat('x', 101)], ['administrative_region' => $rules['administrative_region']])->fails());
    }

    public function test_tc006_characterization_property_rules_do_not_enforce_city_district_membership(): void
    {
        foreach ([new StorePropertyRequest(), new UpdatePropertyRequest()] as $request) {
            $rules = $request->rules();
            $validator = Validator::make(['city' => 'review-city-A', 'district' => 'review-district-from-city-B'], [
                'city' => $rules['city'],
                'district' => $rules['district'],
            ]);
            // Documents the acceptance gap; passing here does not approve TC006.
            $this->assertTrue($validator->passes());
        }
    }

    public function test_tc010_actual_property_form_region_control_renders_without_required(): void
    {
        View::share('errors', new \Illuminate\Support\ViewErrorBag());
        $source = file_get_contents(resource_path('views/tenant/properties/partials/_form.blade.php'));
        $this->assertSame(1, preg_match('/<x-form\.controls\.select name="administrative_region".*?\/>/s', $source, $matches));
        $html = Blade::render($matches[0], ['is_edit' => false, 'property' => null, 'regionOptions' => ['منطقة الرياض' => 'منطقة الرياض']]);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $select = (new \DOMXPath($document))->query('//select[@name="administrative_region"]')->item(0);
        $this->assertNotNull($select);
        $this->assertFalse($select->hasAttribute('required'));
    }

    public function test_tc011_actual_property_district_control_renders_exact_requested_placeholder(): void
    {
        View::share('errors', new \Illuminate\Support\ViewErrorBag());
        $source = file_get_contents(resource_path('views/tenant/properties/partials/_form.blade.php'));
        $this->assertSame(1, preg_match('/<x-form\.controls\.select name="district".*?\/>/s', $source, $matches));
        $html = Blade::render($matches[0], ['is_edit' => false, 'districtOptions' => []]);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        $select = (new \DOMXPath($document))->query('//select[@name="district"]')->item(0);
        $this->assertNotNull($select);
        $this->assertSame('اختر الحي', $select->getAttribute('data-placeholder'));
    }

    public function test_tc002_characterization_trial_password_is_hidden_but_not_encrypted_by_model(): void
    {
        $syntheticPassword = 'Review-Only-Password-42!';
        $request = new TrialRequest(['plain_password' => $syntheticPassword]);
        // Synthetic unsaved instance: no real credential is read or written.
        $this->assertSame($syntheticPassword, $request->getAttributes()['plain_password']);
        $this->assertArrayNotHasKey('plain_password', $request->toArray());
    }
}
