@if ($customer->phoneRequests->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="text-center">
                        <i class="ti ti-phone me-1"></i> رقم الهاتف
                    </th>
                    <th scope="col" class="text-center">
                        <i class="ti ti-flag me-1"></i> الحالة
                    </th>
                    <th scope="col" class="text-center">
                        <i class="ti ti-calendar me-1"></i> تاريخ الطلب
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customer->phoneRequests as $request)
                    <tr class="align-middle">
                        <td class="text-center">
                            @if ($request->request_type === 'add_phone' && isset($request->requested_data['phones']))
                                @php
                                    $phones = $request->requested_data['phones'];
                                    $lastPhone = end($phones);
                                @endphp
                                <span class="fw-bold text-primary">{{ $lastPhone['phone'] }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {!! $request->status_label !!}
                        </td>
                        <td class="text-center">
                            <div class="d-flex flex-column">
                                <span class="fw-semibold">{{ $request->created_at->format('d/m/Y') }}</span>
                                <small class="text-muted">{{ $request->created_at->format('h:i A') }}</small>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-5">
        <i class="ti ti-phone-off fs-1 text-muted mb-3"></i>
        <div class="text-muted">لا توجد طلبات إضافة أرقام هواتف</div>
    </div>
@endif
