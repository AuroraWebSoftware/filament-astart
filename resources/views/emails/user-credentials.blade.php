<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('filament-astart::filament-astart.resources.user.emails.credentials_subject', ['app' => $appName]) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e5e5;
        }
        .header h1 {
            color: #1a1a1a;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .credentials {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .credentials-row {
            display: flex;
            margin-bottom: 10px;
        }
        .credentials-row:last-child {
            margin-bottom: 0;
        }
        .credentials-label {
            font-weight: 600;
            color: #666;
            min-width: 100px;
        }
        .credentials-value {
            color: #1a1a1a;
            font-family: monospace;
            background-color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e5e5e5;
        }
        .btn {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
        }
        .btn:hover {
            background-color: #2563eb;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
            color: #92400e;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>

        <div class="content">
            <p>{{ __('filament-astart::filament-astart.resources.user.emails.greeting', ['name' => $user->name]) }}</p>

            <p>{{ __('filament-astart::filament-astart.resources.user.emails.account_created') }}</p>

            <div class="credentials">
                <div class="credentials-row">
                    <span class="credentials-label">{{ __('filament-astart::filament-astart.resources.user.emails.email_label') }}</span>
                    <span class="credentials-value">{{ $user->email }}</span>
                </div>
                <div class="credentials-row">
                    <span class="credentials-label">{{ __('filament-astart::filament-astart.resources.user.emails.password_label') }}</span>
                    <span class="credentials-value">{{ $password }}</span>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="{{ $loginUrl }}" class="btn">
                    {{ __('filament-astart::filament-astart.resources.user.emails.login_button') }}
                </a>
            </p>

            <div class="warning">
                {{ __('filament-astart::filament-astart.resources.user.emails.security_warning') }}
            </div>
        </div>

        <div class="footer">
            <p>{{ __('filament-astart::filament-astart.resources.user.emails.footer', ['app' => $appName]) }}</p>
        </div>
    </div>
</body>
</html>
