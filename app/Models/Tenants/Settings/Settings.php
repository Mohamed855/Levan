<?php

namespace App\Models\Tenants\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | The attributes that are mass assignable.
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'image',
        'office_name',
        'twilio_account_sid',
        'twilio_auth_token',
        'twilio_whatsapp_from',
        'whatsapp_enabled',
        'horizontal_header_image',
        'vertical_header_image',
        'horizontal_footer_image',
        'vertical_footer_image',
        'signature',
        'sms_provider',
        'sms_api_key',
        'sms_api_secret',
        'sms_api_endpoint',
        'sms_sender_id',
        'sms_from_number',
        'sms_callback_url',
        'sms_enabled',
        'sms_auto_reminders_enabled',
        'sms_rate_limit',
        'support_emails',
        'maintenance_mode',
        'breadcrumb_visibility',
        'force_two_factor_auth',
        'logo_text',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'whatsapp_enabled'           => 'boolean',
        'sms_enabled'                => 'boolean',
        'sms_auto_reminders_enabled' => 'boolean',
        'maintenance_mode'           => 'boolean',
        'breadcrumb_visibility'      => 'boolean',
        'force_two_factor_auth'      => 'boolean',
        'support_emails'             => 'array',
    ];
}
