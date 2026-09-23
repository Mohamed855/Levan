@extends('layouts.layoutMaster')
@section('title', 'فتح تذكرة دعم فني')

@section('content')
<style>
    .support-card {
        border-radius: 15px;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        padding: 25px;
        background: #fff;
    }
    .support-card h3 {
        color: #372632;
        font-weight: bold;
    }
    .form-label {
        font-weight: 600;
        color: #372632;
    }
    .btn-submit {
        background-color: #372632;
        color: #fff;
        border-radius: 10px;
        padding: 10px 30px;
        border: none;
    }
    .btn-submit:hover {
        background-color: #1d2033;
        color: #fff;
    }
</style>

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
                    <option value="low">منخفضة</option>
                    <option value="medium">متوسطة</option>
                    <option value="high">مرتفعة</option>
                </select>
            </div>

            <!-- نوع المشكلة -->
            <div class="mb-3">
                <label class="form-label">نوع المشكلة</label>
                <select name="problem" class="form-control" required>
                    <option value="technical">مشكلة تقنية</option>
                    <option value="billing">مشكلة في الفواتير</option>
                    <option value="account">مشكلة في الحساب</option>
                    <option value="other">أخرى</option>
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
