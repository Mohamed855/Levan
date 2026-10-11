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
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk')->default('tenant_public');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->string('extension', 32)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('category')->index();
            $table->timestamps();
        });

        DB::table('assignments')
            ->whereNotNull('entity_logo')
            ->where('entity_logo', '!=', '')
            ->orderBy('id')
            ->each(function (object $assignment): void {
                DB::table('attachments')->insertOrIgnore([
                    'attachable_type' => 'assignment',
                    'attachable_id' => $assignment->id,
                    'disk' => 'tenant_public',
                    'file_path' => $assignment->entity_logo,
                    'original_name' => basename($assignment->entity_logo),
                    'mime_type' => null,
                    'extension' => pathinfo($assignment->entity_logo, PATHINFO_EXTENSION) ?: null,
                    'file_size' => 0,
                    'category' => 'assignment_image',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
