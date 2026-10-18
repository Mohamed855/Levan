<!doctype html>
<html lang="ar" dir="rtl">
<body>
    <p>مرحبًا {{ $customer_name }}،</p>
    <p>بخصوص رسالتك:</p>
    <blockquote>{{ $message }}</blockquote>
    <p>الرد:</p>
    <blockquote>{{ $reply }}</blockquote>
    <p>مع تحيات {{ $office_name }}.</p>
</body>
</html>
