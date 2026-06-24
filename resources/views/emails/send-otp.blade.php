<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} verification code</title>
</head>
<body style="margin:0; padding:0; background-color:#f4efe6; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4efe6; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px; background-color:#fffdf9; border:1px solid #eadfcf; border-radius:24px; overflow:hidden; box-shadow:0 20px 45px rgba(106, 76, 40, 0.12);">
                    <tr>
                        <td style="padding:32px 32px 0;">
                            <div style="font-size:12px; line-height:1; letter-spacing:0.18em; text-transform:uppercase; color:#a16207; font-weight:700;">
                                {{ config('app.name', 'Laravel') }}
                            </div>
                            <h1 style="margin:12px 0 0; font-size:30px; line-height:1.2; color:#1f2937;">
                                Verify your email
                            </h1>
                            <p style="margin:16px 0 0; font-size:16px; line-height:1.75; color:#4b5563;">
                                Hello {{ $recipientName }}, use the code below to complete your registration.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 0;">
                            <div style="background:linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border:1px solid #fdba74; border-radius:18px; padding:28px 20px; text-align:center;">
                                <div style="font-size:12px; font-weight:700; letter-spacing:0.18em; text-transform:uppercase; color:#9a3412; margin-bottom:12px;">
                                    One-time password
                                </div>
                                <div style="font-size:42px; line-height:1; font-weight:800; letter-spacing:0.35em; color:#111827;">
                                    {{ $otp }}
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 0;">
                            <p style="margin:0; font-size:14px; line-height:1.8; color:#6b7280;">
                                This code expires in 5 minutes. If you did not create an account, you can ignore this message.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px 32px;">
                            <div style="border-top:1px solid #f0e3cf; padding-top:16px; font-size:13px; line-height:1.7; color:#9ca3af;">
                                Need help? Reply to this email and we will take a look.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
