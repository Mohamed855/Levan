<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Models\Admin\PaymentAttempt;
use App\Models\Admin\Tenants\Tenant;

interface PaidCheckoutProvisioner
{
    public function execute(PaymentAttempt $attempt): Tenant;
}
