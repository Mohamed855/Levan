<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
            direction: rtl;
            text-align: right;
        }

        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #372632 0%, #4cc1bb 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .email-header .logo {
            margin-bottom: 15px;
        }

        .email-header .logo img {
            max-height: 60px;
            width: auto;
        }

        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .email-body {
            padding: 30px 25px;
            color: #333333;
            line-height: 1.8;
            font-size: 15px;
        }

        .email-body p {
            margin: 0 0 15px 0;
        }

        .highlight {
            color: #372632;
            font-weight: 600;
        }

        .accent {
            color: #4cc1bb;
            font-weight: 600;
        }

        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }

        .email-footer a {
            color: #372632;
            text-decoration: none;
        }

        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            background-color: #4cc1bb;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #372632;
        }
    </style>
</head>

<body>
    <div class="email-container">
        {{-- رأس البريد --}}
        <div class="email-header">
            {{-- <div class="logo">
                <img src="{{ asset('static/brand/logos/logo.svg') }}" alt="{{ config('app.name') }}">
            </div> --}}
            <h1>{{ config('app.name') }}</h1>
        </div>

        {{-- محتوى البريد --}}
        <div class="email-body">
            {!! nl2br(e($body)) !!}
        </div>

        {{-- تذييل البريد --}}
        <div class="email-footer">
            <p style="margin: 0 0 10px 0;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
            </p>
            <p style="margin: 0;">
                هذا البريد الإلكتروني تم إرساله تلقائياً، يرجى عدم الرد عليه.
            </p>
        </div>
    </div>
</body>

</html>
