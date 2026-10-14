<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class DocumentRequestReasonPolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'document_request_reasons.view';

    protected const ALLOW_SYSTEM_SETTINGS = true;
}
