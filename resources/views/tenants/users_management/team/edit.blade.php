@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل المجموعة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            getUsersByBranch: "{{ tenant_route_name('teams.users-by-branch', ['branchId' => '___BRANCH_ID___']) }}"
        };

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
                ->values()
                ->toArray();
        @endphp

        window.teamData = {
            teamId: {{ $team->id }},
            branchId: {{ $team->branch_id }},
            members: @json($membersData),
            users: @json($users->toArray())
        };
    </script>
    @vite(['resources/assets/js/tenants/user-mangment/team/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">بيانات المجموعة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#members-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الأعضاء والقادة</span>
                                <span class="bs-stepper-subtitle">إدارة الفريق</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('teams.update', $team) }}"
                        method="POST">
                        @csrf
                        @method('PUT')

                        <!-- المرحلة الأولى: المعلومات الأساسية -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">اسم المجموعة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="ادخل اسم المجموعة" value="{{ old('name', $team->name) }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="branch_id">الفرع <span
                                            class="text-danger">*</span></label>
                                    <select name="branch_id" id="branch_id" class="form-control select2"
                                        data-placeholder="اختر الفرع">
                                        <option value=""></option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ old('branch_id', $team->branch_id) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="description">الوصف</label>
                                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="ادخل وصف المجموعة">{{ old('description', $team->description) }}</textarea>
                                </div>

                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.next-button />
                                </x-form.wizard.actions>
                            </div>
                        </div>

                        <!-- المرحلة الثانية: الأعضاء والقادة -->
                        <div id="members-validation" class="content">
                            <div class="row g-6">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="ti ti-users me-2"></i>
                                            إضافة الأعضاء
                                        </h5>
                                        <span class="badge bg-label-primary">الأعضاء: <span
                                                id="members-count">0</span></span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="user_select">اختر مستخدم لإضافته</label>
                                    <select id="user_select" class="form-control select2" data-placeholder="اختر مستخدم">
                                        <option value=""></option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="50%">المستخدم</th>
                                                    <th width="35%">قائد الفريق</th>
                                                    <th width="15%" class="text-center">إجراءات</th>
                                                </tr>
                                            </thead>
                                            <tbody id="members-table-body">
                                                <tr id="no-members-row">
                                                    <td colspan="3" class="text-center text-muted py-4">
                                                        <i class="ti ti-users ti-lg mb-2 d-block"></i>
                                                        لا يوجد أعضاء في المجموعة
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.save-button type="submit" text="تحديث" />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
