@php
$hasAnyActions = isset($hasAnyActions) ? $hasAnyActions : false;
@endphp

@if ($customerFollows && $customerFollows->count() > 0)
<div class="table-responsive">
    <table class="table table-striped align-middle text-center" id="follows-table"
        data-customer-id="{{ $customer->id }}">
        <thead class="table-light">
            <tr>
                <th>الموظف</th>
                <th>وسيلة الاتصال</th>
                <th>نتيجة الاتصال</th>
                <th>التفاصيل</th>
                <th>الإيصال</th>
                <th>الملاحظات</th>
                <th>تاريخ الإضافة</th>
                @if($hasAnyActions)
                <th>الإجراءات</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($customerFollows as $follow)
            @php
            // التحقق من إمكانية التعديل
            $canEdit = true;
            if ($follow->collection_id && $follow->collection) {
            $canEdit = !in_array($follow->collection->status, ['approved', 'rejected']);
            }

            $hasEditButton = $canEdit && auth()->user()->can('customer_follows.edit');
            $hasDeleteButton = auth()->user()->can('customer_follows.delete');
            $hasCurrentActions = $hasEditButton || $hasDeleteButton;
            @endphp
            <tr>
                <td>{{ $follow->createdBy->name ?? 'غير محدد' }}</td>
                <td>{{ $follow->contactMethod->name ?? 'غير محدد' }}</td>
                <td>{{ $follow->contactResult->name ?? 'غير محدد' }}</td>
                <td>
                    @switch($follow->contact_result_id)
                    @case(1)
                    <!-- تم السداد بالكامل -->
                    @if($follow->fullPaymentDetails)
                    المبلغ: {{ number_format($follow->fullPaymentDetails->payment_amount ?? 0, 2) }}
                    <br>
                    التاريخ: {{ $follow->fullPaymentDetails->payment_date?->format('Y-m-d') ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(2)
                    <!-- سداد جزئي -->
                    @if($follow->partialPaymentDetails)
                    المبلغ: {{ number_format($follow->partialPaymentDetails->partial_payment_amount ?? 0, 2) }}
                    <br>
                    التاريخ: {{ $follow->partialPaymentDetails->partial_payment_date?->format('Y-m-d') ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(3)
                    <!-- وعد بالسداد -->
                    @if($follow->promisePaymentDetails)
                    المبلغ الموعود: {{ number_format($follow->promisePaymentDetails->promise_amount ?? 0, 2) }}
                    <br>
                    التاريخ الموعود: {{ $follow->promisePaymentDetails->promise_date?->format('Y-m-d') ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(4)
                    <!-- معاودة الاتصال -->
                    @if($follow->callbackDetails)
                    تاريخ المتابعة: {{ $follow->callbackDetails->callback_date?->format('Y-m-d H:i') ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(9)
                    <!-- طلب مستندات -->
                    @if($follow->documentRequestDetails)
                    تاريخ الطلب: {{ $follow->documentRequestDetails->documents_date?->format('Y-m-d') ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(10)
                    <!-- معترض -->
                    @if($follow->objectionDetails)
                    سبب الاعتراض: {{ $follow->objectionDetails->objection_reason ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @case(11)
                    <!-- حذف مسترد -->
                    @if($follow->recoveryDetails)
                    سبب الاسترداد: {{ $follow->recoveryDetails->recovery_reason ?? '—' }}
                    @else
                    <span class="text-muted">لا توجد تفاصيل</span>
                    @endif
                    @break

                    @default
                    لا توجد تفاصيل
                    @endswitch
                </td>
                <td>
                    @php
                    $attachments = [];

                    if ($follow->fullPaymentDetails?->payment_receipt_path) {
                    $attachments[] = [
                    'path' => $follow->fullPaymentDetails->payment_receipt_path,
                    'type' => 'إيصال سداد'
                    ];
                    }

                    if ($follow->partialPaymentDetails?->partial_payment_receipt_path) {
                    $attachments[] = [
                    'path' => $follow->partialPaymentDetails->partial_payment_receipt_path,
                    'type' => 'إيصال سداد جزئي'
                    ];
                    }

                    if ($follow->recoveryDetails?->recovery_document_path) {
                    $attachments[] = [
                    'path' => $follow->recoveryDetails->recovery_document_path,
                    'type' => 'مستند استرداد'
                    ];
                    }
                    @endphp

                    @if(count($attachments))
                    @foreach($attachments as $attachment)
                    <a href="{{ Storage::disk('tenant_public')->url($attachment['path']) }}" target="_blank"
                        class="text-primary me-2 mb-1" title="{{ $attachment['type'] }}">
                        <i class="ti ti-file" style="font-size: 14px;"></i>
                    </a>
                    @endforeach
                    @else
                    <span class="text-muted">لا يوجد</span>
                    @endif
                </td>


                <td>
                    @if($follow->notes && strlen(trim($follow->notes)) > 0)
                    @php
                    $notesText = strip_tags($follow->notes);
                    $shortNotes = \Illuminate\Support\Str::limit($notesText, 10);
                    @endphp

                    <span class="text-primary follow-note-trigger" style="cursor: pointer;"
                        data-follow-id="{{ $follow->id }}" data-note="{{ e($notesText) }}">
                        {{ $shortNotes }}
                    </span>
                    @else
                    <span class="text-muted">لا توجد ملاحظات</span>
                    @endif
                </td>



                <td>{{ $follow->created_at->locale('ar')->translatedFormat('d M Y, H:i') }}</td>
                @if($hasAnyActions)
                <td>
                    @if($hasCurrentActions)
                    <div class="d-flex justify-content-center align-items-center">
                        @if($hasEditButton)
                        <button type="button" class="btn p-0 me-2 js-edit-follow-btn" data-id="{{ $follow->id }}"
                            title="تعديل">
                            <i class="ti ti-edit text-secondary" style="font-size: 1.2rem;"></i>
                        </button>
                        @endif

                        @if($hasEditButton && $hasDeleteButton)
                        <span class="mx-1"></span>
                        @endif

                        @if($hasDeleteButton)
                        <button type="button" class="btn p-0 js-delete-follow-btn" data-id="{{ $follow->id }}"
                            title="حذف">
                            <i class="ti ti-trash text-secondary" style="font-size: 1.2rem;"></i>
                        </button>
                        @endif
                    </div>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                @endif
            </tr>

            <tr class="follow-note-row d-none" id="note-row-{{ $follow->id }}">
                <td colspan="{{ $hasAnyActions ? 8 : 7 }}" class="overflow-visible align-top">
                    <div class="p-3 border rounded bg-light w-100 overflow-visible">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>الملاحظة</strong>

                            <button type="button" class="btn btn-sm btn-secondary copy-note-btn"
                                data-follow-id="{{ $follow->id }}">
                                نسخ
                            </button>
                        </div>

                        <div id="note-content-{{ $follow->id }}"
                            class="text-muted w-100 h-auto overflow-visible text-break text-wrap">
                        </div>

                    </div>
                </td>
            </tr>

            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-5">
    <i class="ti ti-inbox" style="font-size: 48px; color: #8592a3;"></i>
    <h5 class="text-muted mt-3">لا توجد متابعات لهذا العميل</h5>
</div>
@endif
