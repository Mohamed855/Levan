<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use PHPUnit\Framework\TestCase;

class PublicRequestsStatusTest extends TestCase
{
    public function test_contact_request_statuses_are_english_workflow_keys(): void
    {
        self::assertSame(['pending', 'replied'], array_column(ContactRequestStatus::cases(), 'value'));
    }

    public function test_trial_request_statuses_are_english_workflow_keys(): void
    {
        self::assertSame(['pending', 'approved', 'rejected'], array_column(TrialRequestStatus::cases(), 'value'));
    }
}
