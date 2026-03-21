<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flagged website alert</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size:15px; line-height:1.5; color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                {{-- Warning accent bar --}}
                <tr>
                    <td style="height:4px; background-color:#d97706; font-size:0; line-height:0;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="padding:24px 28px 16px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td>
                                    <p style="margin:0 0 6px 0; font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:#64748b;">Parental WiFi</p>
                                    <span style="display:inline-block; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; background-color:#fffbeb; color:#b45309; border:1px solid #fde68a;">Review suggested</span>
                                </td>
                            </tr>
                        </table>
                        <h1 style="margin:16px 0 0 0; font-size:22px; font-weight:600; color:#0f172a;">Flagged website visit</h1>
                        <p style="margin:12px 0 0 0; font-size:14px; color:#475569;">{{ $payload['preheader'] }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:8px; border-left:4px solid #d97706;">
                            <tr>
                                <td style="padding:16px 18px;">
                                    <p style="margin:0; font-size:14px; color:#334155;">
                                        <strong>{{ $payload['child_or_device_label'] }}</strong> visited a
                                        <strong style="color:#b45309;">flagged</strong> site:
                                        <strong style="color:#92400e;">{{ $payload['url_or_domain'] }}</strong>
                                        (access was allowed and logged for your review).
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px 28px;">
                        <p style="margin:0 0 10px 0; font-size:12px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Event details</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;">
                            <tr>
                                <td style="padding:10px 14px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0; width:38%; font-size:13px; color:#64748b;">
                                    Timestamp
                                </td>
                                <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;">
                                    {{ $payload['event_local_datetime'] }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; color:#64748b;">
                                    Device
                                </td>
                                <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;">
                                    {{ $payload['device_name'] }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; color:#64748b;">
                                    Domain / URL
                                </td>
                                <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a; word-break:break-all;">
                                    {{ $payload['url_or_domain'] }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; color:#64748b;">
                                    IP address
                                </td>
                                <td style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;">
                                    {{ $payload['ip_address'] ?? 'N/A' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px; background-color:#f8fafc; font-size:13px; color:#64748b;">
                                    Severity
                                </td>
                                <td style="padding:10px 14px; font-size:14px; font-weight:600; color:#b45309;">
                                    Warning
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 28px 20px 28px;">
                        <p style="margin:0; font-size:13px; color:#64748b;">
                            <strong style="color:#334155;">Recommended:</strong> Review the context in the dashboard and update flagged rules or talking points with your child if needed.
                        </p>
                        <p style="margin:12px 0 0 0; font-size:12px; color:#94a3b8;">Times shown in {{ $payload['timezone'] }}.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 28px 28px 28px; border-top:1px solid #e2e8f0; background-color:#f8fafc;">
                        <p style="margin:0; font-size:12px; color:#64748b;">Parental WiFi activity alert</p>
                        <p style="margin:12px 0 0 0;">
                            <a href="{{ $payload['dashboard_url'] }}" style="display:inline-block; padding:10px 18px; background-color:#d97706; color:#ffffff !important; text-decoration:none; border-radius:6px; font-size:14px; font-weight:600;">Open dashboard</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
