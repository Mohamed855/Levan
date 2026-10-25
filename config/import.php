<?php

return [
    //    هذا الملف يحتوي على إعدادات عملية الاستيراد

    //  حجم المجموعة (عدد الصفوف لكل Job) - زيادة كبيرة للسرعة
    'chunk_size' => env('IMPORT_CHUNK_SIZE', 400),

    // //  اسم الطابور - الافتراضي
    // 'queue_name' => env('IMPORT_QUEUE_NAME', 'default'),

    // // مهلة انتظار الـ Job (بالثواني) - زيادة للدفعات الأكبر
    // 'job_timeout' => env('IMPORT_JOB_TIMEOUT', 600), // 10 دقائق

    // // عدد محاولات إعادة تشغيل الـ Job
    // 'job_tries' => env('IMPORT_JOB_TRIES', 2), // تقليل المحاولات

    // //  مدة حفظ بيانات الجلسة في الكاش (بالدقائق)
    // 'session_cache_duration' => env('IMPORT_SESSION_CACHE_DURATION', 480), // 8 ساعات

    // // الحد الأقصى لحجم الملف (بالميجابايت)
    // 'max_file_size' => env('IMPORT_MAX_FILE_SIZE', 50),

    // //  الحد الأقصى لعدد الصفوف
    // 'max_rows' => env('IMPORT_MAX_ROWS', 100000),

    // // تفعيل تسجيل العمليات التفصيلي - تعطيل للأداء
    // 'detailed_logging' => env('IMPORT_DETAILED_LOGGING', false),

    // //  تكرار التحديث لحالة التقدم (بالثواني) - أسرع
    // 'progress_update_interval' => env('IMPORT_PROGRESS_UPDATE_INTERVAL', 2),

    // //  إعدادات الأداء المحسنة للملفات الكبيرة
    // 'performance' => [
    //     // بدون تأخير بين الجوبز
    //     'job_delay_seconds' => 0,

    //     // حد أقصى للذاكرة لكل job
    //     'memory_limit' => '1024M',

    //     // تفعيل التحسينات
    //     'optimize_queries' => true,

    //     // تجميع العمليات
    //     'batch_operations' => true,

    //     //  إعدادات التحليل للملفات الكبيرة
    //     'analysis' => [
    //         'max_analysis_rows' => 1000,  // تحليل أول 1000 صف فقط
    //         'sample_data_rows' => 5,      // نموذج 5 صفوف للمعاينة
    //         'memory_limit' => '1024M',    // 1 جيجا للتحليل
    //         'time_limit' => 300           // 5 دقائق
    //     ]
    // ],

    // //  إعدادات شريط التقدم
    // 'progress_display' => [
    //     // عدم عرض الوقت المتوقع
    //     'show_estimated_time' => false,

    //     // عرض الساعة بالتنسيق الإنجليزي
    //     'time_format' => 'en-US',

    //     // سرعة التحديث
    //     'update_frequency' => 3 // ثواني
    // ]
];
