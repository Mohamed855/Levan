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
                'etmam_enabled',
                'etmam_base_url',
                'etmam_system_code',
                'etmam_api_token',
                'etmam_hmac_secret',
                'microsoft_client_id',
                'microsoft_client_secret',
                'microsoft_redirect_uri',
                'microsoft_tenant_id',
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
            if (! Schema::hasColumn('settings', 'etmam_enabled')) {
                $table->boolean('etmam_enabled')->default(false);
            }
            if (! Schema::hasColumn('settings', 'etmam_base_url')) {
                $table->string('etmam_base_url')->nullable();
            }
            if (! Schema::hasColumn('settings', 'etmam_system_code')) {
                $table->string('etmam_system_code')->nullable();
            }
            if (! Schema::hasColumn('settings', 'etmam_api_token')) {
                $table->string('etmam_api_token')->nullable();
            }
            if (! Schema::hasColumn('settings', 'etmam_hmac_secret')) {
                $table->string('etmam_hmac_secret')->nullable();
            }
            if (! Schema::hasColumn('settings', 'microsoft_client_id')) {
                $table->string('microsoft_client_id')->nullable();
            }
            if (! Schema::hasColumn('settings', 'microsoft_client_secret')) {
                $table->text('microsoft_client_secret')->nullable();
            }
            if (! Schema::hasColumn('settings', 'microsoft_redirect_uri')) {
                $table->string('microsoft_redirect_uri')->nullable();
            }
            if (! Schema::hasColumn('settings', 'microsoft_tenant_id')) {
                $table->string('microsoft_tenant_id')->nullable();
            }
        });
    }
};

