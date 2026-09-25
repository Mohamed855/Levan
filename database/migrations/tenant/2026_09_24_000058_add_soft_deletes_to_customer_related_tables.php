<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_letters', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('customer_comment_mentions', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
        
        Schema::table('callback_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('document_request_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('full_payment_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('objection_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('partial_payment_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('promise_payment_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('recovery_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('temporary_contact_loss_details', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_letters', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('customer_comment_mentions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        
        Schema::table('callback_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('document_request_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('full_payment_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('objection_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('partial_payment_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('promise_payment_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('recovery_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('temporary_contact_loss_details', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
