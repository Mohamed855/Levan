<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issuing_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Default foundational Saudi entities if empty
        if (DB::table('issuing_entities')->count() === 0) {
            $firstUserId = DB::table('users')->value('id');
            $defaultEntities = [
                'مركز الإسناد والتصفية (إنفاذ)',
                'محكمة التنفيذ بالرياض',
                'الهيئة العامة لعقارات الدولة',
                'كتابة العدل الأولى',
            ];

            foreach ($defaultEntities as $entityName) {
                DB::table('issuing_entities')->insert([
                    'name' => $entityName,
                    'is_active' => true,
                    'created_by' => $firstUserId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('permissions')) {
            $newPermissions = [
                'issuing_entities.view',
                'issuing_entities.create',
                'issuing_entities.edit',
                'issuing_entities.delete',
                'issuing_entities.toggle_status',
            ];

            $adminRoleId = Schema::hasTable('roles')
                ? DB::table('roles')->where('name', 'Admin')->where('guard_name', 'tenant')->value('id')
                : null;

            foreach ($newPermissions as $perm) {
                $existing = DB::table('permissions')
                    ->where('name', $perm)
                    ->where('guard_name', 'tenant')
                    ->first();

                $permId = $existing?->id;
                if (! $permId) {
                    $permId = DB::table('permissions')->insertGetId([
                        'name' => $perm,
                        'guard_name' => 'tenant',
                        'section' => 'issuing_entities_management',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                if ($adminRoleId && Schema::hasTable('role_has_permissions')) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permId,
                        'role_id' => $adminRoleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('issuing_entities');
    }
};
