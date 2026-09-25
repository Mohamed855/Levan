<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_comment_id')->constrained('customer_comments')->onDelete('cascade');
            $table->foreignId('mentioned_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['customer_comment_id', 'mentioned_user_id'], 'comment_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('customer_comment_mentions', function (Blueprint $table) {
            $table->dropUnique('comment_user_unique');
        });
        Schema::dropIfExists('customer_comment_mentions');
    }
};
