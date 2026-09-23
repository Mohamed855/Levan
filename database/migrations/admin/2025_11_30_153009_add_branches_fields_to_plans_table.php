<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('yearly_renewal_price', 10, 2)->after('price'); // التجديد السنوي
            $table->decimal('extra_user_license_price', 10, 2)->default(0)->after('yearly_renewal_price'); // الترخيص الاضافي


            $table->decimal('discount_percentage', 5, 2)->default(0)->after('extra_user_license_price');

            $table->integer('branches_limit')->nullable()->after('discount_percentage');
            $table->boolean('unlimited_branches')->default(false)->after('branches_limit');

            $table->boolean('domain_management_enabled')->default(false)->after('unlimited_branches');
        });
    }

    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['yearly_renewal_price', 'extra_user_license_price', 'discount_percentage', 'branches_limit', 'unlimited_branches', 'domain_management_enabled']);
        });
    }
};
