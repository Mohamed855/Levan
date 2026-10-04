@if ($payments->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 5%">م</th>
                    <th style="width: 12%">رقم السند</th>
                    <th style="width: 12%">التاريخ</th>
                    <th style="width: 12%">المبلغ</th>
                    <th style="width: 10%">النوع</th>
                    <th style="width: 12%">طريقة السداد</th>
                    <th style="width: 12%">نوع السداد</th>
                    <th style="width: 12%">أضيف بواسطة</th>
                    <th style="width: 13%">اعتمد بواسطة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $index => $payment)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <span class="badge bg-label-info">{{ $payment->receipt_number ?? 'غير محدد' }}</span>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $payment->collection_date ? $payment->collection_date->format('Y-m-d') : 'غير محدد' }}
                            </small>
                        </td>
                        <td>
                            <span class="fw-semibold ">
                                {{ number_format($payment->collected_amount, 2) }}
                            </span>
                        </td>
                        <td>
                            @if ($payment->collection_type === 'cash')
                                <span class="badge bg-label-success">نقدي</span>
                            @else
                                <span class="badge bg-label-warning">مجدول</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $payment->paymentMethod->name ?? 'غير محدد' }}</small>
                        </td>
                        <td>
                            @if ($payment->payment_type === 'settlement')
                                <span class="badge bg-label-primary">بتسوية</span>
                            @else
                                <span class="badge bg-secondary">بدون تسوية</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $payment->addedBy->name ?? 'غير محدد' }}
                            </small>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $payment->approvedBy->name ?? 'غير محدد' }}
                                <br>
                                <span style="font-size: 0.75rem;">
                                    {{ $payment->approved_at ? $payment->approved_at->format('Y-m-d H:i') : '' }}
                                </span>
                            </small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <th colspan="3" class="text-end">الإجمالي : </th>
                    <th class="text-start">
                        <span class="fw-bold ">
                            {{ number_format($payments->sum('collected_amount'), 2) }}
                        </span>
                    </th>
                    <th colspan="5"></th>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="text-center py-5" id="no-payments-message">
        <i class="ti ti-receipt-off" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mb-0 mt-3">لا توجد سدادات مسجلة</p>
        <small class="text-muted">سيتم عرض سجل السدادات المعتمدة هنا</small>
    </div>
@endif
