@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)

@section('vendor-style')
    @vite(['resources/assets/css/tenant/tickets/show.css', 'resources/assets/css/shared/tickets-show.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/tenant/tickets/show.js'])

    @if ($ticket->status === 'resolved_pending_survey')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modalEl = document.getElementById('ticketSurveyModal');
                if (modalEl) {
                    var surveyModal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    surveyModal.show();
                }
            });
        </script>
    @endif
@endsection

@section('content')
    {{-- مودال تقييم التذكرة --}}
    <x-bootstrap.modal id="ticketSurveyModal" title="تقييم التذكرة #{{ $ticket->ticket_number }}" icon="ti-star" size="lg">
        <form action="{{ tenant_route_name('tickets.survey.submit', $ticket->ticket_number) }}" method="POST" novalidate
            data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf

            <div class="text-center py-2">
                <p class="mb-3 fw-bold fs-5">
                    شاركنا تجربتك! هل تم حل مشكلتك بنجاح؟
                </p>
                <div class="d-flex justify-content-center gap-3 mb-4 choice-buttons" data-form-field
                    data-field-name="solved">
                    <input type="radio" class="btn-check" name="solved" id="solvedYes" value="1" autocomplete="off"
                        required data-form-control {{ old('solved') == '1' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success" for="solvedYes">
                        نعم، تم الحل
                    </label>

                    <input type="radio" class="btn-check" name="solved" id="solvedNo" value="0" autocomplete="off"
                        required data-form-control {{ old('solved') == '0' ? 'checked' : '' }}>
                    <label class="btn btn-outline-danger" for="solvedNo">
                        لا، لم تُحل
                    </label>
                </div>

                <div id="ratingSection" style="display: {{ old('solved') == '1' ? 'block' : 'none' }};">
                    <hr class="my-4">

                    @foreach ($surveyQuestions as $field => $label)
                        <div class="my-4">
                            <label class="form-label d-block mb-2 fw-semibold">{{ $label }}</label>

                            <input type="hidden" name="{{ $field }}" value="{{ old($field) }}"
                                data-validation-skip />

                            <div class="d-flex justify-content-center">
                                <div class="survey-stars" data-target="{{ $field }}">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="survey-star" data-value="{{ $i }}">★</span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr class="my-4">
                <div class="mt-3">
                    <textarea name="comment" class="form-control" data-form-control rows="3"
                        placeholder="شاركنا رأيك أو أي ملاحظات لديك...">{{ old('comment') }}</textarea>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>إلغاء
                </button>
                <button type="submit" class="btn btn-primary" id="submitSurveyBtn">
                    <i class="ti ti-send me-1"></i> إرسال التقييم
                </button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>

    @include('shared.tickets._details', [
        'isAdmin' => false,
        'backUrl' => tenant_route_name('tickets.index'),
        'replyAction' => tenant_route_name('tickets.reply', $ticket->ticket_number),
        'sectionName' => is_array($section ?? null)
            ? ($section['name'] ?? 'الدعم الفني')
            : ($section->name ?? 'الدعم الفني'),
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
    @include('shared.tickets._attachment-script')
@endsection
