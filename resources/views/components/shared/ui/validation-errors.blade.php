@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'alert alert-danger mb-4']) }} role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
