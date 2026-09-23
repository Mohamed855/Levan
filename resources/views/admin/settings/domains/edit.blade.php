@extends('admin.layouts.layoutMaster')
@section('content')
    <div class="container">
        <h2>تعديل امتداد دومين</h2>
        <form action="{{ route('admin.settings.domains.update', $domain->id) }}" method="post">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label>الامتداد</label>
                <input type="text" name="domain_extension" class="form-control" value="{{ $domain->domain_extension }}"
                    required>
            </div>
            <div class="form-group">
                <label>السعر</label>
                <input type="number" name="price" step="0.01" class="form-control" value="{{ $domain->price }}"
                    required>
            </div>
            <button class="btn btn-primary mt-2" type="submit">تعديل</button>
        </form>
    </div>
@endsection
