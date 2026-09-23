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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // كود الكوبون
            $table->string('name'); // اسم الكوبون
            $table->enum('discount_type', ['fixed', 'percentage']); // نوع الخصم: نقدي أو نسبة مئوية
            $table->decimal('discount_value', 10, 2); // قيمة الخصم
            $table->decimal('max_discount', 10, 2)->nullable(); // الحد الأقصى للخصم
            $table->date('expiry_date')->nullable(); // تاريخ انتهاء الكوبون
            $table->boolean('is_active')->default(true); // حالة التفعيل
            $table->integer('usage_limit')->nullable(); // عدد مرات الاستخدام المسموح (اختياري)
            $table->integer('used_count')->default(0); // عدد مرات الاستخدام الفعلي

            $table->foreignId('created_by')->constrained('admin_users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('admin_users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
