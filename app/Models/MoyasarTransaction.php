<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoyasarTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_id',
        'amount',
        'currency',
        'fee',
        'status',
        'payment_method',
        'description',
        'customer_name',
        'customer_email',
        'customer_phone',
        'card_brand',
        'card_last_four',
        'metadata',
        'moyasar_response',
        'error_type',
        'error_message',
        'paid_at',
        'refunded_at',
        'captured_at',
        'voided_at',
    ];

    protected $casts = [
        'amount'            => 'decimal:2',
        'fee'               => 'decimal:2',
        'metadata'          => 'array',
        'moyasar_response'  => 'array',
        'paid_at'           => 'datetime',
        'refunded_at'       => 'datetime',
        'captured_at'       => 'datetime',
        'voided_at'         => 'datetime',
    ];

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['initiated', 'authorized']);
    }

    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isPending()
    {
        return in_array($this->status, ['initiated', 'authorized']);
    }

    public function canBeRefunded()
    {
        return in_array($this->status, ['paid', 'captured'])
            && !$this->refunded_at;
    }

    public function canBeVoided()
    {
        return $this->status === 'authorized'
            && !$this->voided_at;
    }

    public function getAmountInHalalaAttribute()
    {
        return (int) ($this->amount * 100);
    }

    public function getFormattedAmountAttribute()
    {
        $symbol = match ($this->currency) {
            'SAR' => 'ر.س',
            'USD' => '$',
            'AED' => 'د.إ',
            default => $this->currency
        };

        return number_format($this->amount, 2) . ' ' . $symbol;
    }


    public function getPaymentMethodNameAttribute()
    {
        return match ($this->payment_method) {
            'creditcard'        => 'بطاقة ائتمانية',
            'mada'              => 'مدى',
            'stcpay'            => 'STC Pay',
            'samsungpay'        => 'Samsung Pay',
            default => $this->payment_method
        };
    }


    public function getStatusNameAttribute()
    {
        return match ($this->status) {
            'initiated'         => 'قيد المعالجة',
            'authorized'        => 'معتمد',
            'paid'              => 'مدفوع',
            'failed'            => 'فاشل',
            'refunded'          => 'مسترجع',
            'voided'            => 'ملغي',
            'captured'          => 'محصّل',
            default => $this->status
        };
    }


    public function updateFromMoyasarResponse(array $response)
    {
        $data = [
            'status'            => $response['status'],
            'moyasar_response'  => $response,
        ];

        // تحديث طريقة الدفع إذا كانت موجودة
        if (isset($response['source']['type'])) {
            $data['payment_method'] = $response['source']['type'];
        }

        // بيانات البطاقة
        if (isset($response['source']['company'])) {
            $data['card_brand'] = $response['source']['company'];
        }
        if (isset($response['source']['number'])) {
            $data['card_last_four'] = substr($response['source']['number'], -4);
        }

        // الرسوم
        if (isset($response['fee'])) {
            $data['fee'] = $response['fee'] / 100; // من هللات إلى ريال
        }

        // التواريخ حسب الحالة
        if ($response['status'] === 'paid' && !$this->paid_at) {
            $data['paid_at'] = now();
        }

        // معلومات الخطأ
        if ($response['status'] === 'failed') {
            $data['error_type'] = $response['source']['message'] ?? null;
            $data['error_message'] = $response['source']['message'] ?? 'فشل الدفع';
        }

        return $this->update($data);
    }


    public static function createFromMoyasarResponse(array $response, $transactionable = null)
    {
        $transaction = new self([
            'payment_id' => $response['id'],
            'amount' => $response['amount'] / 100, // من هللات إلى ريال
            'currency' => $response['currency'],
            'status' => $response['status'],
            'description' => $response['description'] ?? null,
            'moyasar_response' => $response,
        ]);

        if ($transactionable) {
            $transaction->transactionable()->associate($transactionable);
        }

        $transaction->save();
        $transaction->updateFromMoyasarResponse($response);

        return $transaction;
    }
}
