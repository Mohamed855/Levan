<?php

namespace App\Models\Tenants\GeneralSetting\MessageTemplate;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_templates';

    protected $fillable = [
        'name',
        'content',
        'category',
        'channel',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع المستخدم المنشئ
    |--------------------------------------------------------------------------
    */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | نطاقات الاستعلام
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ///////////////////////////////////////////////////////////////////////
    |--------------------------------------------------------------------------
    */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /*
    |--------------------------------------------------------------------------
    | فئات القوالب المتاحة
    |--------------------------------------------------------------------------
    */
    public static function getCategories()
    {
        return [
            'customers' => 'العملاء',
            'company' => 'الشركات',
            'users' => 'الموظفين',
            'general' => 'عام',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public static function getChannels()
    {
        return [
            'sms' => 'إرسال عبر الرسائل النصية (SMS)',
            'email' => 'إرسال عبر البريد الإلكتروني',
            'whatsapp' => 'إرسال عبر تطبيق واتساب',
            'multiple' => 'إرسال عبر قنوات متعددة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | المتغيرات العامة المتاحة في جميع القوالب
    |--------------------------------------------------------------------------
    */
    public static function getGeneralVariables()
    {
        return [
            '{date}' => 'التاريخ الحالي',
            '{time}' => 'الوقت الحالي',
            '{datetime}' => 'التاريخ والوقت الحالي',
            '{year}' => 'السنة الحالية',
            '{month}' => 'الشهر الحالي',
            '{day}' => 'اليوم الحالي',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | متغيرات العملاء من جدول Customer
    |--------------------------------------------------------------------------
    */
    public static function getCustomerVariables()
    {
        return [
            // معلومات العميل الأساسية
            '{customer_name}' => 'اسم العميل',

            /* ── الأسماء المنفصلة ── */
            '{first_name}' => 'الاسم الأول',
            '{father_name}' => 'اسم الأب',
            '{grandfather_name}' => 'اسم الجد',
            '{family_name}' => 'اسم العائلة',

            '{customer_type_label}' => 'نوع العميل',
            '{identity_number}' => 'رقم الهوية (اخر 4 أرقام)',
            '{phone}' => 'رقم الهاتف',
            '{account_number}' => 'رقم الحساب',

            // رقم الخدمة  من البيانات  الديناميكية للمحافظ ان وجد
            '{service_number}' => 'رقم الخدمة',

            // === تمت إضافة المتغير الجديد هنا ===
            '{claim_number}' => 'رقم المطالبة',
            // =====================================

            // المعلومات المالية
            '{formatted_debt_amount}' => 'مبلغ الدين',
            '{formatted_commission_rate}' => 'معدل العمولة',

            // التواريخ - استخدام المنسقة فقط
            '{formatted_contract_date}' => 'تاريخ العقد',
            '{formatted_default_date}' => 'تاريخ التعثر',

            // المعلومات الإضافية
            '{nationality}' => 'الجنسية',

            // معلومات الحالة والعلاقات
            '{status_label}' => 'حالة العميل',
            '{relationship_manager}' => 'مدير العلاقات',

            // معلومات الشركة والمحفظة
            '{company_name}' => 'اسم الشركة',
            '{portfolio_name}' => 'اسم المحفظة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | متغيرات التحصيل من جدول Collection
    |--------------------------------------------------------------------------
    */
    public static function getCollectionVariables()
    {
        return [
            // معلومات التحصيل الأساسية
            '{receipt_number}' => 'رقم السند',
            '{collected_amount}' => 'المبلغ المحصل',
            '{formatted_collected_amount}' => 'المبلغ المحصل مُنسق',
            '{collection_type}' => 'نوع التحصيل',
            '{collection_type_label}' => 'نوع التحصيل (مترجم)',
            '{payment_type}' => 'نوع السداد',
            '{payment_type_label}' => 'نوع السداد (مترجم)',
            // معلومات الدفع والطريقة
            '{payment_method}' => 'طريقة الدفع',
            '{closing_code}' => 'رمز الإقفال',
            // التواريخ
            '{collection_date}' => 'تاريخ التحصيل',
            '{formatted_collection_date}' => 'تاريخ التحصيل مُنسق',
            // معلومات الحالة والاعتماد
            '{collection_status}' => 'حالة التحصيل',
            '{status_label}' => 'تسمية حالة التحصيل',
            '{approved_at}' => 'تاريخ الاعتماد',
            '{rejection_reason}' => 'سبب الرفض',
            // معلومات المستخدمين
            '{added_by}' => 'مُضاف بواسطة',
            '{approved_by}' => 'معتمد بواسطة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | متغيرات المستخدمين من جدول User
    |--------------------------------------------------------------------------
    */
    public static function getUserVariables()
    {
        return [
            '{user_name}' => 'اسم المستخدم',
            '{user_email}' => 'بريد المستخدم الإلكتروني',
            '{user_phone}' => 'هاتف المستخدم',
            '{user_status}' => 'حالة المستخدم',
            '{current_user_name}' => 'اسم المستخدم الحالي',
            '{current_user_email}' => 'بريد المستخدم الحالي',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | متغيرات الشركات (Company)
    |--------------------------------------------------------------------------
    | حسب طلبك: الأساس "اسم الشركة" فقط.
    */
    public static function getCompanyVariables()
    {
        return [
            '{company_name}' => 'اسم الشركة',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | متغيرات المفوضين بالشركة (الأول والثاني فقط)
    |--------------------------------------------------------------------------
    | اسم / هاتف / بريد / منصب
    */
    public static function getCompanyAuthorizedVariables()
    {
        return [
            '{authorized_1_name}' => 'اسم المفوض الأول',
            '{authorized_1_phone}' => 'هاتف المفوض الأول',
            '{authorized_1_email}' => 'بريد المفوض الأول',
            '{authorized_1_position}' => 'منصب المفوض الأول',
            '{authorized_2_name}' => 'اسم المفوض الثاني',
            '{authorized_2_phone}' => 'هاتف المفوض الثاني',
            '{authorized_2_email}' => 'بريد المفوض الثاني',
            '{authorized_2_position}' => 'منصب المفوض الثاني',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على المتغيرات حسب الفئة
    |--------------------------------------------------------------------------
    */
    public static function getVariablesByCategory($category = null)
    {
        return array_merge(
            self::getGeneralVariables(),
            self::getUserVariables(),
            self::getCustomerVariables(),
            self::getCollectionVariables(),
            self::getCompanyVariables(),
            self::getCompanyAuthorizedVariables()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | المتغيرات الافتراضية (للتوافق مع الكود القديم)
    |--------------------------------------------------------------------------
    */
    public static function getDefaultVariables()
    {
        return self::getGeneralVariables();
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على جميع المتغيرات المتاحة حسب الفئة
    |--------------------------------------------------------------------------
    */
    public function getAvailableVariablesAttribute()
    {
        return self::getVariablesByCategory($this->category);
    }

    /*
    |--------------------------------------------------------------------------
    | تجميع المتغيرات حسب النوع للعرض المنظم
    |--------------------------------------------------------------------------
    */
    public static function getGroupedVariables($category = null)
    {
        return [
            'عام' => self::getGeneralVariables(),
            'المستخدمين' => self::getUserVariables(),
            'العملاء' => self::getCustomerVariables(),
            'التحصيل' => self::getCollectionVariables(),
            'الشركات' => array_merge(
                self::getCompanyVariables(),
                self::getCompanyAuthorizedVariables()
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تسمية الفئة
    |--------------------------------------------------------------------------
    */
    public function getCategoryNameAttribute()
    {
        $categories = self::getCategories();

        return $categories[$this->category] ?? $this->category;
    }

    /*
    |--------------------------------------------------------------------------
    | حالة النشاط
    |--------------------------------------------------------------------------
    */
    public function getStatusClassAttribute()
    {
        return $this->is_active ? 'bg-label-success' : 'bg-label-danger';
    }

    /*
    |--------------------------------------------------------------------------
    |  تسمية الحالة
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'نشط' : 'غير نشط';
    }

    /*
    |--------------------------------------------------------------------------
    |  معاينة المحتوى مع استبدال المتغيرات - محدثة
    |--------------------------------------------------------------------------
    */
    public function previewContent($sampleData = [])
    {
        $content = $this->content;

        $defaultSampleData = [
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('H:i'),
            'datetime' => now()->format('Y-m-d H:i'),
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),

            // متغيرات العملاء - بدون تكرار
            'customer_name' => 'أحمد محمد السعيد',
            'first_name' => 'أحمد',
            'father_name' => 'محمد',
            'grandfather_name' => 'سعيد',
            'family_name' => 'الأنصارى',

            'customer_type_label' => 'فردي',
            'identity_number' => '******7890',
            'phone' => '966501234567',
            'account_number' => 'ACC123456',
            'service_number' => 'SRV987654',
            'formatted_debt_amount' => '15,000.00',
            'formatted_commission_rate' => '5.00%',
            'formatted_contract_date' => '10 ديسمبر 2024',
            'formatted_default_date' => '20 فبراير 2025',
            'nationality' => 'السعودية',
            'status_label' => 'نشط',
            'relationship_manager' => 'محمد أحمد',
            'company_name' => 'شركة المثال للتجارة',
            'portfolio_name' => 'محفظة الرياض',

            // متغيرات التحصيل
            'receipt_number' => 'REC001234',
            'collected_amount' => '5000.00',
            'formatted_collected_amount' => '5,000.00',
            'collection_type' => 'cash',
            'collection_type_label' => 'نقدي',

            // متغيرات المستخدمين
            'user_name' => 'سعد الأحمد',
            'user_email' => 'saad@company.com',
            'current_user_name' => auth()->user()->name ?? 'المستخدم الحالي',

            // شركات
            'company_name' => 'شركة المثال للتجارة',

            // المفوض الأول
            'authorized_1_name' => 'عبدالله السالم',
            'authorized_1_phone' => '966501111111',
            'authorized_1_email' => 'abdullah@example.com',
            'authorized_1_position' => 'مدير علاقات',

            // المفوض الثاني
            'authorized_2_name' => 'خالد العتيبي',
            'authorized_2_phone' => '966502222222',
            'authorized_2_email' => 'khaled@example.com',
            'authorized_2_position' => 'نائب مدير',

        ];

        $mergedData = array_merge($defaultSampleData, $sampleData);

        // استبدال المتغيرات
        foreach ($mergedData as $key => $value) {
            $content = str_replace('{'.$key.'}', $value, $content);
        }

        return $content;
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function getChannelNameAttribute()
    {
        $map = self::getChannels();

        return $map[$this->channel] ?? $this->channel;
    }
}
