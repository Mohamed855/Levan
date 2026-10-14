@extends('admin.layouts.layoutMaster')
@section('content')
    <div class="container">
        <h2>إضافة امتداد دومين</h2>
        <form action="{{ route('admin.settings.domains.store') }}" method="post">
            @csrf
            <div class="row">
                <div class="form-group">
                    <label>الامتداد</label>
                    <input type="text" name="domain_extension" class="form-control" placeholder=".com" required>
                </div>
                <div class="form-group">
                    <label>السعر</label>
                    <input type="number" name="price" placeholder="2000" step="0.01" class="form-control" required>
                </div>
            </div>
            <div class="d-flex gap-2 mt-2">
                <x-form.wizard.draft-button />
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
@endsection
