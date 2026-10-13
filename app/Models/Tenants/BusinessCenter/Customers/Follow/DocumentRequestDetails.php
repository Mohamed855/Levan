<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Follow;

use App\Models\Tenants\GeneralSetting\DocumentRequest\SettingsDocumentRequestReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentRequestDetails extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'document_request_details';

    protected $fillable = [
        'contact_result_id',
        'documents_date',
        'documents_reason',
        'documents_reason_id',
    ];

    protected $casts = [
        'documents_date' => 'date',
    ];

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class, 'contact_result_id');
    }

    public function documentReason()
    {
        return $this->belongsTo(SettingsDocumentRequestReason::class, 'documents_reason_id');
    }
}
