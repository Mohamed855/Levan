<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}">
</head>
<body class="bg-body">
    <main class="container py-5" style="max-width: 480px;">
        <section class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">{{ $title }}</h1>
                <p class="text-muted">أرسلنا رمزاً مكوناً من ستة أرقام إلى بريدك الإلكتروني. تنتهي صلاحيته خلال 10 دقائق.</p>
                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ $verificationRoute }}">
                    @csrf
                    <label class="form-label" for="code">رمز التحقق</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="form-control @error('code') is-invalid @enderror" required autofocus>
                    <button class="btn btn-primary w-100 mt-3" type="submit">تحقق وتابع</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
