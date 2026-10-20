
document.addEventListener('DOMContentLoaded', function () {
    const solvedYes = document.getElementById('solvedYes');
    const solvedNo = document.getElementById('solvedNo');
    const ratingSection = document.getElementById('ratingSection');
    const submitButton = document.getElementById('submitSurveyBtn');

    // إخفاء قسم التقييم في البداية
    if (ratingSection) {
        ratingSection.style.display = 'none';
    }

    // عند اختيار "نعم، تم الحل"
    if (solvedYes) {
        solvedYes.addEventListener('change', function () {
            if (this.checked) {
                ratingSection.style.display = 'block';
                submitButton.textContent = 'إرسال التقييم';
            }
        });
    }

    // عند اختيار "لا، لم يتم الحل"
    if (solvedNo) {
        solvedNo.addEventListener('change', function () {
            if (this.checked) {
                ratingSection.style.display = 'none';
                submitButton.textContent = 'إرسال';

                // مسح قيم التقييم
                document.querySelectorAll(
                    'input[name="agent_rating"], input[name="system_rating"], input[name="solution_rating"]'
                ).forEach(function (input) {
                    input.value = '';
                });

                // إزالة النجوم النشطة
                document.querySelectorAll('.survey-star').forEach(function (star) {
                    star.classList.remove('active');
                });
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    // نجوم الاستبيان في المودال
    document.querySelectorAll('.survey-stars').forEach(function (wrapper) {
        const targetName = wrapper.getAttribute('data-target');
        const hiddenInput = document.querySelector('input[name="' + targetName + '"]');
        const stars = wrapper.querySelectorAll('.survey-star');

        if (!hiddenInput) return;

        function setRating(value) {
            hiddenInput.value = value;
            stars.forEach(function (star) {
                const starValue = parseInt(star.getAttribute('data-value'), 10);
                if (starValue <= value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function highlightStars(value) {
            stars.forEach(function (star) {
                const starValue = parseInt(star.getAttribute('data-value'), 10);
                if (starValue <= value) {
                    star.classList.add('hover');
                } else {
                    star.classList.remove('hover');
                }
            });
        }

        function removeHighlight() {
            stars.forEach(function (star) {
                star.classList.remove('hover');
            });
        }

        // في حالة الرجوع من فشل تحقق (validation error)
        if (hiddenInput.value) {
            setRating(parseInt(hiddenInput.value, 10));
        }

        stars.forEach(function (star) {
            // عند النقر على النجمة
            star.addEventListener('click', function () {
                const value = parseInt(this.getAttribute('data-value'), 10);
                setRating(value);
            });

            // عند المرور بالماوس على النجمة
            star.addEventListener('mouseenter', function () {
                const value = parseInt(this.getAttribute('data-value'), 10);
                highlightStars(value);
            });
        });

        // عند الخروج من منطقة النجوم
        wrapper.addEventListener('mouseleave', function () {
            removeHighlight();
        });
    });

    // نجوم تقييم الردود
    document.querySelectorAll('.reply-rating').forEach(function (wrapper) {
        const stars = wrapper.querySelectorAll('.reply-star-btn');

        function highlightReplyStars(hoveredButton) {
            const hoveredValue = parseInt(hoveredButton.getAttribute('value'), 10);

            stars.forEach(function (star) {
                const starValue = parseInt(star.getAttribute('value'), 10);
                if (starValue <= hoveredValue) {
                    star.classList.add('hover-reply');
                } else {
                    star.classList.remove('hover-reply');
                }
            });
        }

        function removeReplyHighlight() {
            stars.forEach(function (star) {
                star.classList.remove('hover-reply');
            });
        }

        stars.forEach(function (star) {
            // عند المرور بالماوس
            star.addEventListener('mouseenter', function () {
                highlightReplyStars(this);
            });
        });

        // عند الخروج من منطقة النجوم
        wrapper.addEventListener('mouseleave', function () {
            removeReplyHighlight();
        });
    });
});
