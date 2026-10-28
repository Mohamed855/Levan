<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Models\Tenants\Assignments\Assignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuctionValidationScenariosTest extends TestCase
{
    public function test_end_time_before_or_equal_to_start_time_on_same_day_fails_validation(): void
    {
        $request = new StoreAuctionRequest();
        $request->merge([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_time' => '18:00',
            'end_time' => '10:00',
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), [
            'start_date' => $rules['start_date'],
            'end_date' => $rules['end_date'],
            'start_time' => $rules['start_time'],
            'end_time' => $rules['end_time'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('end_time'));
        $this->assertStringContainsString('وقت نهاية المزاد يجب أن يكون بعد وقت البداية', $validator->errors()->first('end_time'));
    }

    public function test_equal_start_and_end_time_on_same_day_fails_validation(): void
    {
        $request = new StoreAuctionRequest();
        $request->merge([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_time' => '14:00',
            'end_time' => '14:00',
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), [
            'start_date' => $rules['start_date'],
            'end_date' => $rules['end_date'],
            'start_time' => $rules['start_time'],
            'end_time' => $rules['end_time'],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('end_time'));
    }

    public function test_end_time_after_start_time_on_same_day_passes_time_validation(): void
    {
        $request = new StoreAuctionRequest();
        $request->merge([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-01',
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), [
            'start_date' => $rules['start_date'],
            'end_date' => $rules['end_date'],
            'start_time' => $rules['start_time'],
            'end_time' => $rules['end_time'],
        ]);

        $this->assertFalse($validator->errors()->has('end_time'));
    }

    public function test_end_time_can_be_earlier_hour_when_end_date_is_after_start_date(): void
    {
        $request = new StoreAuctionRequest();
        $request->merge([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'start_time' => '18:00',
            'end_time' => '10:00',
        ]);

        $rules = $request->rules();
        $validator = Validator::make($request->all(), [
            'start_date' => $rules['start_date'],
            'end_date' => $rules['end_date'],
            'start_time' => $rules['start_time'],
            'end_time' => $rules['end_time'],
        ]);

        $this->assertFalse($validator->errors()->has('end_time'));
    }

    public function test_auction_dates_outside_assignment_duration_fail_validation(): void
    {
        $assignment = new Assignment([
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-30',
            'is_active' => true,
        ]);

        // Case A: start_date before assignment start_date
        $requestA = new StoreAuctionRequest();
        $requestA->setResolvedAssignment($assignment);
        $requestA->merge([
            'assignment_id' => 999,
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-20',
        ]);

        $rulesA = $requestA->rules();
        // Remove exists rule for pure unit test
        $assignmentRulesA = array_filter($rulesA['assignment_id'], fn ($r) => ! is_object($r) || ! str_contains(get_class($r), 'Exists'));
        $validatorA = Validator::make($requestA->all(), [
            'assignment_id' => $assignmentRulesA,
            'start_date' => $rulesA['start_date'],
            'end_date' => $rulesA['end_date'],
        ]);

        $this->assertTrue($validatorA->fails());
        $this->assertTrue($validatorA->errors()->has('assignment_id') || $validatorA->errors()->has('start_date'));
        $this->assertStringContainsString('تاريخ بداية المزاد يجب ألا يسبق تاريخ بداية الإسناد', $validatorA->errors()->first());

        // Case B: end_date after assignment end_date
        $requestB = new StoreAuctionRequest();
        $requestB->setResolvedAssignment($assignment);
        $requestB->merge([
            'assignment_id' => 999,
            'start_date' => '2026-11-15',
            'end_date' => '2026-12-05',
        ]);

        $rulesB = $requestB->rules();
        $assignmentRulesB = array_filter($rulesB['assignment_id'], fn ($r) => ! is_object($r) || ! str_contains(get_class($r), 'Exists'));
        $validatorB = Validator::make($requestB->all(), [
            'assignment_id' => $assignmentRulesB,
            'start_date' => $rulesB['start_date'],
            'end_date' => $rulesB['end_date'],
        ]);

        $this->assertTrue($validatorB->fails());
        $this->assertTrue($validatorB->errors()->has('assignment_id') || $validatorB->errors()->has('end_date'));
        $this->assertStringContainsString('تاريخ نهاية المزاد يجب ألا يتجاوز تاريخ نهاية الإسناد', $validatorB->errors()->first());
    }

    public function test_expired_assignment_fails_validation(): void
    {
        $assignment = new Assignment([
            'start_date' => '2025-01-01',
            'end_date' => '2025-02-01', // Expired
            'is_active' => true,
        ]);

        $request = new StoreAuctionRequest();
        $request->setResolvedAssignment($assignment);
        $request->merge([
            'assignment_id' => 999,
            'start_date' => '2025-01-10',
            'end_date' => '2025-01-20',
        ]);

        $rules = $request->rules();
        $assignmentRules = array_filter($rules['assignment_id'], fn ($r) => ! is_object($r) || ! str_contains(get_class($r), 'Exists'));
        $validator = Validator::make($request->all(), [
            'assignment_id' => $assignmentRules,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('assignment_id'));
        $this->assertStringContainsString('منتهي الصلاحية', $validator->errors()->first('assignment_id'));
    }

    public function test_inactive_assignment_fails_validation(): void
    {
        $futureDate = Carbon::now()->addMonths(2)->format('Y-m-d');
        $assignment = new Assignment([
            'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'end_date' => $futureDate,
            'is_active' => false, // Inactive
        ]);

        $request = new StoreAuctionRequest();
        $request->setResolvedAssignment($assignment);
        $request->merge([
            'assignment_id' => 999,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => $futureDate,
        ]);

        $rules = $request->rules();
        $assignmentRules = array_filter($rules['assignment_id'], fn ($r) => ! is_object($r) || ! str_contains(get_class($r), 'Exists'));
        $validator = Validator::make($request->all(), [
            'assignment_id' => $assignmentRules,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('assignment_id'));
        $this->assertStringContainsString('غير نشط', $validator->errors()->first('assignment_id'));
    }

    public function test_valid_assignment_and_dates_pass_validation(): void
    {
        $futureStart = Carbon::now()->startOfDay()->addDays(5);
        $futureEnd = Carbon::now()->startOfDay()->addDays(30);

        $assignment = new Assignment([
            'start_date' => $futureStart->copy()->subDays(2),
            'end_date' => $futureEnd->copy()->addDays(10),
            'is_active' => true,
        ]);

        $request = new StoreAuctionRequest();
        $request->setResolvedAssignment($assignment);
        $request->merge([
            'assignment_id' => 999,
            'start_date' => $futureStart->format('Y-m-d'),
            'end_date' => $futureEnd->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $rules = $request->rules();
        $assignmentRules = array_filter($rules['assignment_id'], fn ($r) => ! is_object($r) || ! str_contains(get_class($r), 'Exists'));
        $validator = Validator::make($request->all(), [
            'assignment_id' => $assignmentRules,
            'start_date' => $rules['start_date'],
            'end_date' => $rules['end_date'],
            'start_time' => $rules['start_time'],
            'end_time' => $rules['end_time'],
        ]);

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->toArray(), JSON_UNESCAPED_UNICODE));
    }
}
