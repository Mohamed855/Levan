@extends('admin.layouts.layoutMaster')
@section('title','عينات قوالب المزادات')
@section('content')
<div class="card"><div class="card-header d-flex justify-content-between"><h5>مكتبة عينات القوالب</h5><span class="text-muted">تُدار مركزيًا وتظهر حسب الصلاحية</span></div><div class="card-body"><div id="auction-template-samples-library" data-library-url="{{ route('admin.auction-template-samples.library') }}" class="row g-3"></div></div></div>
@endsection
