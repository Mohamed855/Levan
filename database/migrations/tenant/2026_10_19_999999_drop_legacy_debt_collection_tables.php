<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely drops all legacy debt collection and obsolete CRM tables from existing tenant databases.
     */
    public function up(): void
    {
        $tables = [
            'payment_reconciliations',
            'customer_settlement_details',
            'customer_settlements',
            'customer_payment_plan_details',
            'customer_payment_plans',
            'customer_payments',
            'customer_transactions',
            'customer_follows',
            'customer_relationship_history',
            'customer_comment_mentions',
            'customer_comments',
            'customer_update_requests',
            'customer_distributions',
            'customer_sms',
            'customer_notes',
            'customer_attachments',
            'customer_field_values',
            'customer_phones',
            'task_history',
            'task_attachments',
            'task_notes',
            'tasks',
            'collection_process_sessions',
            'collection_targets',
            'collections',
            'work_periods',
            'contract_commissions',
            'contract_debt_types',
            'contracts',
            'portfolio_fields',
            'portfolio_settings',
            'customers',
            'portfolios',
            'import_session_rows',
            'import_sessions',
            'message_individual_logs',
            'message_logs',
            'message_templates',
            'settings_document_request_reasons',
            'settings_contact_results',
            'settings_contact_methods',
            'settings_customer_status',
            'settings_debt_types',
            'settings_countries',
            'settings_payment_methods',
            'transfer_domains',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decommissioned legacy tables are not restored.
    }
};
