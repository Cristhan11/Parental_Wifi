{{-- Matches reporting emails: table layout, slate palette, inline styles for clients. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email verification</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size:15px; line-height:1.5; color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <tr>
                    <td style="height:4px; background-color:#ca8a04; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding:24px 28px 16px 28px;">
                        <p style="margin:0 0 6px 0; font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:#64748b;">Parental WiFi</p>
                        <span style="display:inline-block; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; background-color:#fef9c3; color:#854d0e; border:1px solid #fde047;">Account security</span>
                        <h1 style="margin:16px 0 0 0; font-size:22px; font-weight:600; color:#0f172a;">Verify your email</h1>
                        <p style="margin:12px 0 0 0; font-size:14px; color:#475569;">Hello {{ $userName }}, use the code below to confirm this address. If you did not create an account, you can ignore this message.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 24px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                            <tr>
                                <td style="padding:20px 24px; text-align:center;">
                                    <p style="margin:0 0 8px 0; font-size:12px; font-weight:600; letter-spacing:0.06em; text-transform:uppercase; color:#64748b;">Your verification code</p>
                                    <p style="margin:0; font-size:32px; font-weight:700; letter-spacing:0.35em; font-family: ui-monospace, 'Cascadia Code', 'Segoe UI Mono', Menlo, monospace; color:#0f172a;">{{ $code }}</p>
                                    <p style="margin:12px 0 0 0; font-size:13px; color:#64748b;">This code expires in {{ $expiresMinutes }} minutes.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px 28px;">
                        <p style="margin:0; font-size:13px; color:#64748b;">Enter the code on the verification page after you sign in. For your security, do not share this code with anyone.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px 28px 28px; border-top:1px solid #e2e8f0; background-color:#f8fafc;">
                        <p style="margin:0; font-size:12px; color:#64748b;">Parental WiFi · Email verification</p>
                        <p style="margin:12px 0 0 0;">
                            <a href="{{ $verifyUrl }}" style="display:inline-block; padding:10px 18px; background-color:#ca8a04; color:#ffffff !important; text-decoration:none; border-radius:6px; font-size:14px; font-weight:600;">Open verification page</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
