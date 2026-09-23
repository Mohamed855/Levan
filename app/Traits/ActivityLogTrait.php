<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait ActivityLogTrait
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(static::getLogName())
            ->setDescriptionForEvent(fn($event) => $this->getEventDescription($event))
            ->dontLogIfAttributesChangedOnly(['updated_at', 'updated_by']);

        if (method_exists($this, 'getLogAttributes')) {
            $attributes = static::getLogAttributes();
            if (!empty($attributes)) {
                $options->logOnly($attributes);
            } else {
                $options->logFillable();
            }
        } else {
            $options->logFillable();
        }

        if (method_exists($this, 'getIgnoredAttributes')) {
            $ignored = static::getIgnoredAttributes();
            if (!empty($ignored)) {
                $options->logExcept($ignored);
            }
        }

        return $options;
    }

    protected function getEventDescription(string $eventName): string
    {
        $entityType = static::getModelNameArabic();
        $entityName = $this->getDisplayName();

        return match ($eventName) {
            'created'  => "تم إضافة {$entityType} : {$entityName}",
            'updated'  => "تم تحديث {$entityType}: {$entityName}",
            'deleted'  => "تم حذف {$entityType}: {$entityName}",
            'restored' => "تم استعادة {$entityType}: {$entityName}",
            default    => "تم {$eventName} {$entityType}: {$entityName}",
        };
    }

    protected function getDisplayName(): string
    {
        return $this->name ?? $this->title ?? $this->code ?? "#{$this->id}";
    }

    public static function getFieldLabel(string $field): string
    {
        $translations = static::getFieldTranslations();
        if (isset($translations[$field])) {
            return $translations[$field];
        }

        $global = __('activitylog.fields.' . $field);
        if ($global !== 'activitylog.fields.' . $field) {
            return $global;
        }

        return ucfirst(str_replace('_', ' ', $field));
    }

    public static function getValueLabel(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return 'فارغ';
        }

        $translations = static::getValueTranslations();
        if (isset($translations[$field][$value])) {
            return $translations[$field][$value];
        }

        return match (true) {
            is_bool($value) => $value ? 'نعم' : 'لا',
            is_array($value) => implode(', ', $value),
            $value instanceof \Carbon\Carbon => $value->format('Y-m-d H:i'),
            default => (string) $value,
        };
    }

    // الدوال الافتراضية
    public static function getLogName(): string
    {
        return 'السجل العام';
    }

    public static function getModelNameArabic(): string
    {
        return 'عنصر';
    }

    public static function getFieldTranslations(): array
    {
        return [];
    }

    public static function getValueTranslations(): array
    {
        return [];
    }

    //  دالة اختيارية: تحديد الحقول المراد تسجيلها
    public static function getLogAttributes(): array
    {
        return []; // فارغة = سجل كل شيء
    }

    //  دالة اختيارية: تحديد الحقول المستثناة
    public static function getIgnoredAttributes(): array
    {
        return [];
    }
}
