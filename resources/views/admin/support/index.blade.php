@extends('layouts.layoutMaster')
@section('title', 'فتح تذكرة دعم فني')

@section('content')
<div class="container py-5">
    <div class="support-card">
        <h3 class="mb-4"><i class="ti ti-headset"></i> فتح تذكرة دعم فني</h3>

        <form action="#" method="POST" enctype="multipart/form-data">
            @csrf
            <!-- العنوان -->
            <div class="mb-3">
                <label class="form-label">عنوان التذكرة</label>
                <input type="text" name="title" class="form-control" placeholder="اكتب عنوان مختصر للمشكلة" required>
            </div>

            <!-- الأولوية -->
            <div class="mb-3">
                <label class="form-label">الأولوية</label>
                <select name="priority" class="form-control" required>
                                        <x-form.controls.enum-options :enum="\App\Enums\Admin\Support\SupportPriority::class" :selected="old('priority')" />
                                    </select>
            </div>

            <!-- نوع المشكلة -->
            <div class="mb-3">
                <label class="form-label">نوع المشكلة</label>
                <select name="problem" class="form-control" required>
                                        <x-form.controls.enum-options :enum="\App\Enums\Admin\Support\SupportProblemType::class" :selected="old('problem')" />
                                    </select>
            </div>

            <!-- التفاصيل -->
            <div class="mb-3">
                <label class="form-label">تفاصيل المشكلة</label>
                <textarea name="description" class="form-control" rows="5" placeholder="اشرح المشكلة بالتفصيل" required></textarea>
            </div>

            <!-- صورة -->
            <div class="mb-3">
                <label class="form-label">إرفاق صورة (اختياري)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>

            <!-- زر الإرسال -->
            <button type="submit" class="btn-submit">
                <i class="ti ti-send"></i> إرسال التذكرة
            </button>
        </form>
    </div>
</div>
@endsection
