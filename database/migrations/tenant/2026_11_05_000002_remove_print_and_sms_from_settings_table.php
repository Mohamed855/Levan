<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $columns = [
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
                'sms_rate_limit',
                'sms_enabled',
                'sms_auto_reminders_enabled',
            ];

            $droppable = array_filter($columns, fn ($col) => Schema::hasColumn('settings', $col));

            if (! empty($droppable)) {
                $table->dropColumn(array_values($droppable));
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('settings', 'horizontal_header_image')) {
                $table->string('horizontal_header_image')->nullable();
            }
            if (! Schema::hasColumn('settings', 'vertical_header_image')) {
                $table->string('vertical_header_image')->nullable();
            }
            if (! Schema::hasColumn('settings', 'horizontal_footer_image')) {
                $table->string('horizontal_footer_image')->nullable();
            }
            if (! Schema::hasColumn('settings', 'vertical_footer_image')) {
                $table->string('vertical_footer_image')->nullable();
            }
            if (! Schema::hasColumn('settings', 'signature')) {
                $table->string('signature')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_provider')) {
                $table->string('sms_provider')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_api_key')) {
                $table->string('sms_api_key')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_api_secret')) {
                $table->string('sms_api_secret')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_api_endpoint')) {
                $table->string('sms_api_endpoint')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_sender_id')) {
                $table->string('sms_sender_id')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_from_number')) {
                $table->string('sms_from_number')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_callback_url')) {
                $table->string('sms_callback_url')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_rate_limit')) {
                $table->integer('sms_rate_limit')->nullable();
            }
            if (! Schema::hasColumn('settings', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(true);
            }
            if (! Schema::hasColumn('settings', 'sms_auto_reminders_enabled')) {
                $table->boolean('sms_auto_reminders_enabled')->default(false);
            }
        });
    }
};

