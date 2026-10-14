<?php

declare(strict_types=1);

namespace App\Policies\Tenants\GeneralSetting;

final class MessageTemplatePolicy extends LookupDeletePolicy
{
    protected const PERMISSION = 'message_templates.view';
}
