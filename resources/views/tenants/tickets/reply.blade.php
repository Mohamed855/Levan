@extends('tenants.layouts.layoutMaster')

@section('title', 'جميع الردود')

@section('content')
    <div class="row">
        <div class="col-12">

            {{-- Header Section --}}
            <div class="filter-section">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4 class="mb-0">
                            <i class="ti ti-message-circle me-2"></i>جميع الردود من الدعم الفني
                        </h4>
                        <small class="text-muted">عرض جميع الردود على التذاكر المرسلة</small>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-primary">
                            <i class="ti ti-refresh me-1"></i>تحديث البيانات
                        </button>
                    </div>
                </div>
            </div>

            {{-- الرد الأول - تم الرد عليه --}}
            <div class="reply-card">
                <div class="reply-header">
                    <div class="ticket-info">
                        <span class="ticket-id">#1234</span>
                        <strong>مشكلة في تسجيل الدخول</strong>
                        <span class="badge badge-status status-answered">تم الرد</span>
                    </div>
                    <div class="reply-date">
                        <i class="ti ti-clock me-1"></i>
                        منذ 3 ساعات
                    </div>
                </div>

                <div class="reply-content">
                    شكراً لتواصلك معنا. تم حل المشكلة بنجاح. كان هناك خطأ في كلمة المرور المدخلة. يرجى التأكد من
                    إدخال البيانات الصحيحة والمحاولة مرة أخرى. في حال استمرار المشكلة، يرجى التواصل معنا مباشرة.
                </div>

                <div class="rating-section">
                    <div class="rating-info">
                        <span class="rating-label">تقييم الرد:</span>
                        <div class="rating-stars" data-reply-id="1">
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star"></i>
                        </div>
                    </div>
                    <div class="rating-value">4 / 5</div>
                </div>
            </div>

            {{-- الرد الثاني - لم يتم التقييم --}}
            <div class="reply-card">
                <div class="reply-header">
                    <div class="ticket-info">
                        <span class="ticket-id">#1235</span>
                        <strong>استفسار عن الفوترة</strong>
                        <span class="badge badge-status status-answered">تم الرد</span>
                    </div>
                    <div class="reply-date">
                        <i class="ti ti-clock me-1"></i>
                        منذ 5 ساعات
                    </div>
                </div>

                <div class="reply-content">
                    مرحباً بك، بخصوص استفسارك عن الفوترة، يتم إصدار الفاتورة في أول كل شهر ويتم إرسالها على البريد
                    الإلكتروني المسجل لديك. يمكنك أيضاً تحميل الفواتير السابقة من لوحة التحكم قسم الفواتير.
                </div>

                <div class="rating-section">
                    <div class="rating-info">
                        <span class="rating-label">تقييم الرد:</span>
                        <div class="rating-stars" data-reply-id="2">
                            <i class="ti ti-star"></i>
                            <i class="ti ti-star"></i>
                            <i class="ti ti-star"></i>
                            <i class="ti ti-star"></i>
                            <i class="ti ti-star"></i>
                        </div>
                    </div>
                    <small class="text-muted">لم يتم التقييم بعد</small>
                </div>
            </div>

            {{-- الرد الثالث - تقييم كامل --}}
            <div class="reply-card">
                <div class="reply-header">
                    <div class="ticket-info">
                        <span class="ticket-id">#1230</span>
                        <strong>طلب ميزة جديدة</strong>
                        <span class="badge badge-status status-answered">تم الرد</span>
                    </div>
                    <div class="reply-date">
                        <i class="ti ti-clock me-1"></i>
                        11 نوفمبر 2025, 02:30 م
                    </div>
                </div>

                <div class="reply-content">
                    نشكرك على اقتراحك القيم. تم إضافة طلبك إلى قائمة الميزات المطلوبة وسيتم دراسته من قبل فريق التطوير.
                    سنقوم بإشعارك فور توفر هذه الميزة في النظام.
                </div>

                <div class="rating-section">
                    <div class="rating-info">
                        <span class="rating-label">تقييم الرد:</span>
                        <div class="rating-stars" data-reply-id="3">
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                        </div>
                    </div>
                    <div class="rating-value">5 / 5</div>
                </div>
            </div>

            {{-- الرد الرابع - قيد الانتظار --}}
            <div class="reply-card">
                <div class="reply-header">
                    <div class="ticket-info">
                        <span class="ticket-id">#1240</span>
                        <strong>بلاغ عن خطأ في النظام</strong>
                        <span class="badge badge-status status-answered">تم الرد</span>
                    </div>
                    <div class="reply-date">
                        <i class="ti ti-clock me-1"></i>
                        أمس, 09:15 م
                    </div>
                </div>

                <div class="reply-content">
                    تم استلام بلاغك وتم إحالته لفريق التطوير للمعالجة. الخطأ المبلغ عنه تحت المراجعة حالياً وسيتم إصلاحه
                    في أقرب وقت ممكن. نعتذر عن أي إزعاج قد يسببه هذا الخطأ.
                </div>

                <div class="rating-section">
                    <div class="rating-info">
                        <span class="rating-label">تقييم الرد:</span>
                        <div class="rating-stars" data-reply-id="4">
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star filled"></i>
                            <i class="ti ti-star"></i>
                            <i class="ti ti-star"></i>
                        </div>
                    </div>
                    <div class="rating-value">3 / 5</div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // التعامل مع التقييم بالنجوم
            const allStars = document.querySelectorAll('.rating-stars');

            allStars.forEach(starsContainer => {
                const stars = starsContainer.querySelectorAll('i');

                stars.forEach((star, index) => {
                    // عند الضغط على نجمة
                    star.addEventListener('click', function() {
                        const replyId = starsContainer.dataset.replyId;
                        const rating = index + 1;

                        // تحديث النجوم
                        stars.forEach((s, i) => {
                            if (i <= index) {
                                s.classList.add('filled');
                            } else {
                                s.classList.remove('filled');
                            }
                        });

                        // تحديث أو إضافة قيمة التقييم
                        const ratingSection = starsContainer.closest('.rating-section');
                        let ratingValue = ratingSection.querySelector('.rating-value');

                        if (!ratingValue) {
                            // إزالة النص "لم يتم التقييم بعد"
                            const smallText = ratingSection.querySelector('small');
                            if (smallText) smallText.remove();

                            // إضافة قيمة التقييم الجديدة
                            ratingValue = document.createElement('div');
                            ratingValue.className = 'rating-value';
                            ratingSection.appendChild(ratingValue);
                        }

                        ratingValue.textContent = `${rating} / 5`;

                        // إظهار رسالة نجاح
                        if (typeof toastr !== 'undefined') {
                            toastr.success('شكراً لتقييمك!');
                        } else {
                            alert('شكراً لتقييمك!');
                        }

                        console.log(`تم تقييم الرد #${replyId} بـ ${rating} نجوم`);
                    });

                    // تأثير hover
                    star.addEventListener('mouseenter', function() {
                        stars.forEach((s, i) => {
                            if (i <= index) {
                                s.style.color = '#ffc107';
                            }
                        });
                    });

                    star.addEventListener('mouseleave', function() {
                        stars.forEach((s, i) => {
                            if (!s.classList.contains('filled')) {
                                s.style.color = '#ddd';
                            }
                        });
                    });
                });
            });
        });
    </script>
@endsection
