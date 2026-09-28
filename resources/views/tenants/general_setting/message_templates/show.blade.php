@extends('tenants.layouts.layoutMaster')

@section('title', 'عرض قالب الرسالة')

@section('content')
    <div class="row">
        {{-- المحتوى --}}
        <div class="col-12">
            <div class="tab-content p-0">
                {{-- تفاصيل القالب --}}
                <div class="tab-pane fade show active" id="templateDetails">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">تفاصيل القالب</h5>
                        </div>
                        <div class="card-body">
                            {{-- بطاقة المنشئ --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        @php
                                            $avatar = $messageTemplate->creator
                                                ? $messageTemplate->creator->profile_photo_url
                                                : asset('img/logo.svg');
                                        @endphp
                                        <img src="{{ $avatar }}" class="rounded-circle" width="70" height="70">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-0">{{ $messageTemplate->creator->name ?? 'غير محدد' }}</h5>
                                        <small>{{ $messageTemplate->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge {{ $messageTemplate->is_active ? 'bg-success' : 'bg-secondary' }} fs-6">
                                            {{ $messageTemplate->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- بيانات القالب في بطاقات --}}
                            <div class="row mb-4">
                                <!-- اسم القالب -->
                                <div class="col-md-6 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-template me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">اسم القالب</h6>
                                                    <span class="fw-bold">{{ $messageTemplate->name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الفئة -->
                                <div class="col-md-6 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-category me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">الفئة</h6>
                                                    <span class="fw-bold">{{ $messageTemplate->category_name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- الوصف --}}
                            @if($messageTemplate->description)
                            <div class="mb-4">
                                <h6><i class="ti ti-message-circle me-1"></i>الوصف:</h6>
                                <div class="border rounded p-3">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $messageTemplate->description }}</pre>
                                </div>
                            </div>
                            @endif

                            {{-- محتوى القالب --}}
                            <div>
                                <h6><i class="ti ti-file-text me-1"></i>محتوى القالب</h6>
                                <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{!! $messageTemplate->content !!}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function() {
            if (window.location.hash) {
                $('.nav-tabs a[href="' + window.location.hash + '"]').tab('show');
            }
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                history.pushState(null, null, e.target.hash);
            });
        });
    </script>
@endsection
