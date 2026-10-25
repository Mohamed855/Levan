@extends('admin.layouts.layoutMaster')
@section('title', $sample->exists ? 'تعديل عينة قالب' : 'إضافة عينة قالب')
@section('content')
<div class="card"><div class="card-header"><h5>{{ $sample->exists ? 'تعديل عينة قالب' : 'إضافة عينة قالب' }}</h5></div><div class="card-body">
<form method="post" action="{{ $sample->exists ? route('admin.auction-template-samples.update',$sample) : route('admin.auction-template-samples.store') }}">@csrf @if($sample->exists) @method('PUT') @endif
<div class="mb-3"><label class="form-label">الاسم</label><input name="name" class="form-control" required value="{{ old('name',$sample->name) }}"></div>
<div class="mb-3"><label class="form-label">النوع</label><select name="type" class="form-select" required>@foreach($typeOptions as $option)<option value="{{ $option['value'] }}" @selected(($sample->type?->value ?? old('type'))===$option['value'])>{{ $option['label'] }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">الوصف</label><textarea name="description" class="form-control">{{ old('description',$sample->description) }}</textarea></div>
<input type="hidden" name="schema" value="{{ json_encode($sample->schema ?: \App\Support\Auctions\TemplateSchema::sample('brochure')) }}">
<button class="btn btn-primary">حفظ</button></form></div></div>
@endsection
