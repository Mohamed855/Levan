@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل المجموعة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @php
        $membersData = $team->members
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'is_leader' => $member->is_team_leader,
                ];
            })
            ->values();
    @endphp
    <script>
        window.teamData = {
            members: @json($membersData),
            users: @json($users->values())
        };
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenants/user-mangment/team/validation.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'basic-info-validation', 'title' => 'المعلومات الأساسية'],
            ['id' => 'members-validation', 'title' => 'الأعضاء والقائد'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="team-wizard" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="team-form" action="{{ tenant_route_name('teams.update', $team) }}" method="POST" novalidate
                    data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="basic-info-validation" :fields="['name', 'description']">
                        <div class="row g-6">
                            <x-form.controls.input name="name" label="اسم المجموعة" :value="$team->name" placeholder="أدخل اسم المجموعة" required col="col-md-6" />
                            <x-form.controls.textarea name="description" label="الوصف" :value="$team->description" placeholder="أدخل وصف المجموعة" rows="3" col="col-md-6" />
                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button :href="tenant_route_name('teams.index')" />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="members-validation" :fields="['members', 'leaders']">
                        <div class="row g-6">
                            <x-form.controls.select
                                name="members"
                                id="user_select"
                                :control-name="false"
                                label="إضافة عضو"
                                placeholder="اختر مستخدماً"
                                col="col-md-6"
                                :select2="true"
                                data-validation-skip
                            >
                                <x-slot:append>
                                    <div id="team-members-inputs"></div>
                                </x-slot:append>
                            </x-form.controls.select>
                            <div class="col-12" data-form-field data-field-name="leaders">
                                <div id="team-leaders-inputs"></div>
                                <div class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><i class="ti ti-users me-2" aria-hidden="true"></i>أعضاء المجموعة</h5>
                                    <span class="badge bg-label-primary">الأعضاء: <span id="members-count">0</span></span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light"><tr><th>المستخدم</th><th>قائد المجموعة</th><th class="text-center">الإجراءات</th></tr></thead>
                                        <tbody id="members-table-body"></tbody>
                                    </table>
                                </div>
                            </div>
                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.save-button text="حفظ التعديلات" />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
