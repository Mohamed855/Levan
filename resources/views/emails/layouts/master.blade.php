<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <title>@yield('title', config('app.name', 'Levan'))</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, a, h1, h2, h3, h4, span { font-family: Tahoma, Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap');
        
        * {
            box-sizing: border-box;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #f1f5f9;
            font-family: 'IBM Plex Sans Arabic', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            direction: rtl;
            text-align: right;
            line-height: 1.6;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            padding: 0;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
            max-width: 100%;
            height: auto;
        }
        a {
            color: #4f46e5;
            text-decoration: none;
        }
        .main-wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 24px 12px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .email-header {
            background-color: #0f172a;
            padding: 28px 24px 24px 24px;
            text-align: center;
        }
        .email-header .logo {
            margin: 0 auto 10px auto;
            max-height: 42px;
            display: inline-block;
        }
        .email-header .platform-title {
            color: #f8fafc;
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.2px;
        }
        .email-header .platform-subtitle {
            color: #94a3b8;
            font-size: 12px;
            margin: 4px 0 0 0;
            font-weight: 400;
        }
        .email-body {
            padding: 32px 28px;
            color: #334155;
            font-size: 14.5px;
        }
        .greeting {
            font-size: 17px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 14px 0;
        }
        .lead-text {
            font-size: 14.5px;
            color: #475569;
            margin: 0 0 20px 0;
            line-height: 1.7;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 18px;
            background-color: transparent !important;
        }
        .status-badge.success { background-color: transparent !important; color: #047857; border: 1px solid #059669; }
        .status-badge.warning { background-color: transparent !important; color: #b45309; border: 1px solid #d97706; }
        .status-badge.danger  { background-color: transparent !important; color: #b91c1c; border: 1px solid #dc2626; }
        .status-badge.info    { background-color: transparent !important; color: #1d4ed8; border: 1px solid #2563eb; }
        .status-badge.primary { background-color: transparent !important; color: #334155; border: 1px solid #64748b; }

        .info-card {
            background-color: transparent !important;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px 20px;
            margin: 20px 0;
        }
        .info-card-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 12px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .info-row {
            margin-bottom: 10px;
            font-size: 13.5px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
            font-weight: 500;
            display: inline-block;
            min-width: 130px;
        }
        .info-value {
            color: #0f172a;
            font-weight: 600;
        }

        .highlight-box {
            background-color: transparent !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 18px;
            margin: 20px 0;
            text-align: center;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace, Tahoma;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 6px;
            color: #0f172a;
            background-color: transparent !important;
            display: inline-block;
            padding: 8px 18px;
            border-radius: 6px;
            border: 1px solid #0f172a;
            margin: 10px 0 6px 0;
        }
        .otp-expiry {
            font-size: 12px;
            color: #64748b;
            margin: 6px 0 0 0;
        }

        .cta-button-wrapper {
            margin: 26px 0;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff !important;
            font-size: 14px;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none !important;
        }
        .cta-button:hover {
            background-color: #1e293b;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 6px;
            font-size: 13px;
            line-height: 1.6;
            margin: 18px 0;
            background-color: transparent !important;
            border: 1px solid #e2e8f0;
        }
        .alert-box.info {
            border: 1px solid #cbd5e1;
            border-right: 3px solid #2563eb;
            color: #1e293b;
        }
        .alert-box.warning {
            border: 1px solid #cbd5e1;
            border-right: 3px solid #d97706;
            color: #1e293b;
        }
        .alert-box.danger {
            border: 1px solid #cbd5e1;
            border-right: 3px solid #dc2626;
            color: #1e293b;
        }

        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 24px 0;
        }

        .email-footer {
            background-color: transparent !important;
            border-top: 1px solid #e2e8f0;
            padding: 22px 24px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            line-height: 1.8;
        }
        .footer-office-info {
            margin-bottom: 12px;
        }
        .footer-office-info span {
            display: inline-block;
            margin: 0 6px;
        }
        .footer-note {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                border-radius: 8px !important;
            }
            .email-header {
                padding: 26px 18px 22px 18px !important;
            }
            .email-body {
                padding: 26px 18px !important;
            }
            .email-footer {
                padding: 20px 16px !important;
            }
            .otp-code {
                font-size: 26px !important;
                letter-spacing: 5px !important;
                padding: 8px 16px !important;
            }
            .info-label {
                display: block !important;
                margin-bottom: 2px !important;
            }
            .cta-button {
                display: block !important;
                width: 100% !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="center">
                    <div class="email-container">
                        <!-- Header -->
                        <div class="email-header">
                            <a href="{{ config('app.url', 'https://levan.sa') }}" target="_blank" style="display: inline-block;">
                                <img src="{{ $brandLogo ?? url('static/brand/logos/lot/trans-profile-white.png') }}" alt="{{ $brandName ?? 'لوت Levan' }}" class="logo" />
                            </a>
                            <h1 class="platform-title">{{ $brandName ?? 'منصة لوت Levan' }}</h1>
                            <p class="platform-subtitle">{{ $brandTagline ?? 'منصة إدارة المزادات والممتلكات العقارية الذكية' }}</p>
                        </div>

                        <!-- Body -->
                        <div class="email-body">
                            @yield('content')
                        </div>

                        <!-- Footer -->
                        <div class="email-footer">
                            <div class="footer-office-info">
                                <strong>{{ $officeName ?? ($brandName ?? 'لوت Levan') }}</strong>
                                @if(!empty($officeAddress))
                                    <br /><span>{{ $officeAddress }}</span>
                                @endif
                                <br />
                                @if(!empty($commercialRegister))
                                    <span>س.ت: {{ $commercialRegister }}</span>
                                @endif
                                @if(!empty($taxNumber))
                                    <span>• الرقم الضريبي: {{ $taxNumber }}</span>
                                @endif
                                @if(!empty($officePhone))
                                    <span>• الهاتف: <span dir="ltr">{{ $officePhone }}</span></span>
                                @endif
                                @if(!empty($officeEmail))
                                    <span>• البريد: {{ $officeEmail }}</span>
                                @endif
                            </div>
                            <div class="footer-note">
                                تم إرسال هذه الرسالة تلقائياً من نظام {{ $brandName ?? 'لوت Levan' }}.
                                @if(empty($allowDirectReply))
                                    يرجى عدم الرد المباشر على هذا البريد الإلكتروني.
                                @endif
                                <br />
                                جميع الحقوق محفوظة © {{ date('Y') }} {{ $brandName ?? 'لوت Levan' }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
