<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropBranchColumn('collection_targets');
        $this->dropBranchColumn('teams');
        $this->dropBranchColumn('users');

        Schema::dropIfExists('user_branch_access');
        Schema::dropIfExists('branches');
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('phone')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('status')->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $this->restoreBranchColumn('users');
        $this->restoreBranchColumn('teams');
        $this->restoreBranchColumn('collection_targets');

        if (! Schema::hasTable('user_branch_access')) {
            Schema::create('user_branch_access', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    private function dropBranchColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
    }

    private function restoreBranchColumn(string $tableName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
        });
    }
};
