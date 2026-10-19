<?php

declare(strict_types=1);

namespace App\Models\Admin;

use App\Models\Admin\Tenants\Tenant;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Client extends Tenant
{
    protected $table = 'tenants';

    /**
     * Alias 'name' to 'company_name' for compatibility with LOT contracts.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->company_name,
            set: fn ($value) => ['company_name' => $value],
        );
    }

    /**
     * Alias 'contact_name' to 'primary_contact_name'.
     */
    protected function contactName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->primary_contact_name,
            set: fn ($value) => ['primary_contact_name' => $value],
        );
    }

    /**
     * Alias 'contact_email' to 'primary_contact_email'.
     */
    protected function contactEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->primary_contact_email,
            set: fn ($value) => ['primary_contact_email' => $value],
        );
    }

    /**
     * Alias 'contact_phone' to 'primary_contact_phone'.
     */
    protected function contactPhone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->primary_contact_phone,
            set: fn ($value) => ['primary_contact_phone' => $value],
        );
    }
}
