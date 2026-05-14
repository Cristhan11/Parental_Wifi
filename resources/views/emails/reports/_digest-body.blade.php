{{-- Shared HTML body for daily / weekly / monthly digest emails. Included by daily-digest, weekly-digest, monthly-digest views. Data: ReportingDigestService::buildDigestPayload --}}
@php
    $devices = $payload['devices'] ?? [];
    $registered = (int) ($payload['registered_devices_count'] ?? count($devices));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $payload['title'] }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size:15px; line-height:1.5; color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:8px; border:1px solid #e2e8f0; overflow:hidden;">
                <tr>
                    <td style="padding:28px 28px 8px 28px; border-bottom:1px solid #e2e8f0;">
                        <p style="margin:0 0 4px 0; font-size:12px; letter-spacing:0.06em; text-transform:uppercase; color:#64748b;">Parental WiFi</p>
                        <h1 style="margin:0; font-size:22px; font-weight:600; color:#0f172a;">{{ $payload['title'] }}</h1>
                        <p style="margin:12px 0 0 0; font-size:14px; color:#475569;">{{ $payload['preheader'] }}</p>
                    </td>
                </tr>
                @if (! empty($payload['preview_banner']))
                    <tr>
                        <td style="padding:0 28px 16px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fffbeb; border:1px solid #fde68a; border-radius:6px;">
                                <tr>
                                    <td style="padding:12px 14px; font-size:13px; color:#92400e; line-height:1.45;">
                                        {!! $payload['preview_banner'] !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:20px 28px 8px 28px;">
                        <p style="margin:0; font-size:13px; color:#64748b;">
                            <strong style="color:#334155;">Reporting period</strong><br>
                            {{ $payload['period_start_local']->format('M d, Y H:i') }}
                            &mdash;
                            {{ $payload['period_end_local']->format('M d, Y H:i') }}
                            <span style="color:#94a3b8;">({{ $payload['timezone'] }})</span>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 28px 20px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                            <tr>
                                <td style="padding:16px 18px;">
                                    <p style="margin:0 0 10px 0; font-size:12px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Family overview</p>
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td width="50%" style="padding:4px 8px 4px 0; vertical-align:top;">
                                                <p style="margin:0; font-size:12px; color:#64748b;">Devices registered</p>
                                                <p style="margin:4px 0 0 0; font-size:18px; font-weight:600; color:#0f172a;">{{ $registered }}</p>
                                            </td>
                                            <td width="50%" style="padding:4px 0 4px 8px; vertical-align:top;">
                                                <p style="margin:0; font-size:12px; color:#64748b;">Active this period</p>
                                                <p style="margin:4px 0 0 0; font-size:18px; font-weight:600; color:#0f172a;">{{ $payload['active_devices_count'] }}</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" style="padding-top:12px; border-top:1px solid #e2e8f0;">
                                                <p style="margin:0 0 6px 0; font-size:12px; color:#64748b;">Totals — violations</p>
                                                <p style="margin:0; font-size:14px; color:#334155;">
                                                    <span style="color:#b91c1c; font-weight:600;">{{ $payload['violations_summary']['blocked_count'] }}</span> blocked
                                                    &nbsp;&middot;&nbsp;
                                                    <span style="color:#b45309; font-weight:600;">{{ $payload['violations_summary']['flagged_count'] }}</span> flagged
                                                </p>
                                                <p style="margin:8px 0 0 0; font-size:12px; color:#64748b;">Totals — usage &amp; grants</p>
                                                <p style="margin:4px 0 0 0; font-size:14px; color:#334155;">
                                                    {{ $payload['time_usage_and_grants']['total_usage_minutes'] }} min used
                                                    &nbsp;&middot;&nbsp;
                                                    {{ $payload['time_usage_and_grants']['grants_count'] }} grant(s)
                                                    &nbsp;&middot;&nbsp;
                                                    {{ $payload['time_usage_and_grants']['total_granted_minutes'] }} min granted
                                                </p>
                                                <p style="margin:8px 0 0 0; font-size:12px; color:#64748b;">Totals — bandwidth (data transferred)</p>
                                                <p style="margin:4px 0 0 0; font-size:14px; color:#334155;">
                                                    {{ $payload['bandwidth']['family_total_formatted'] ?? '0 GB (0 MB)' }}
                                                    <span style="font-size:12px; color:#94a3b8;">({{ $payload['bandwidth']['source'] ?? 'browsing_logs' }})</span>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @if (count($devices) === 0)
                    <tr>
                        <td style="padding:8px 28px 28px 28px;">
                            <p style="margin:0; padding:16px; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; color:#92400e; font-size:14px;">
                                No child devices are registered yet. Add a device in the dashboard to see per-device reports here.
                            </p>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td style="padding:4px 28px 8px 28px;">
                            <p style="margin:0; font-size:12px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">By device</p>
                        </td>
                    </tr>
                    @foreach ($devices as $device)
                        <tr>
                            <td style="padding:8px 28px 16px 28px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:8px; border-left:4px solid #2563eb;">
                                    <tr>
                                        <td style="padding:16px 18px 8px 18px;">
                                            <p style="margin:0; font-size:16px; font-weight:600; color:#0f172a;">{{ $device['name'] }}</p>
                                            <p style="margin:4px 0 0 0; font-size:12px; color:#94a3b8;">Device ID {{ $device['id'] }}</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 18px 12px 18px;">
                                            <p style="margin:0 0 6px 0; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Violations</p>
                                            <p style="margin:0; font-size:14px; color:#334155;">
                                                <span style="color:#b91c1c; font-weight:600;">{{ $device['violations_summary']['blocked_count'] }}</span> blocked
                                                &nbsp;&middot;&nbsp;
                                                <span style="color:#b45309; font-weight:600;">{{ $device['violations_summary']['flagged_count'] }}</span> flagged
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 18px 12px 18px;">
                                            <p style="margin:0 0 8px 0; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Time usage &amp; grants (this period)</p>
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
                                                <tr>
                                                    <td style="width:72px; font-size:12px; color:#64748b; vertical-align:middle;">Usage</td>
                                                    <td style="vertical-align:middle;">
                                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e2e8f0; border-radius:4px; height:10px;">
                                                            <tr>
                                                                <td style="width:{{ max(0, min(100, (int) ($device['usage_bar_percent'] ?? 0))) }}%; background:#2563eb; border-radius:4px; height:10px; font-size:0; line-height:0;">&nbsp;</td>
                                                                <td style="background:transparent;">&nbsp;</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td style="width:56px; text-align:right; font-size:13px; font-weight:600; color:#0f172a; vertical-align:middle;">{{ $device['time_usage_and_grants']['total_usage_minutes'] }}m</td>
                                                </tr>
                                            </table>
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="width:72px; font-size:12px; color:#64748b; vertical-align:middle;">Granted</td>
                                                    <td style="vertical-align:middle;">
                                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e2e8f0; border-radius:4px; height:10px;">
                                                            <tr>
                                                                <td style="width:{{ max(0, min(100, (int) ($device['grants_bar_percent'] ?? 0))) }}%; background:#059669; border-radius:4px; height:10px; font-size:0; line-height:0;">&nbsp;</td>
                                                                <td style="background:transparent;">&nbsp;</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td style="width:56px; text-align:right; font-size:13px; font-weight:600; color:#0f172a; vertical-align:middle;">{{ $device['time_usage_and_grants']['total_granted_minutes'] }}m</td>
                                                </tr>
                                            </table>
                                            <p style="margin:8px 0 0 0; font-size:12px; color:#64748b;">{{ $device['time_usage_and_grants']['grants_count'] }} time grant(s) in this period</p>
                                            <p style="margin:6px 0 0 0; font-size:12px; color:#64748b;">Bandwidth: <span style="font-size:13px; font-weight:600; color:#0f172a;">{{ $device['bandwidth']['bytes_total_formatted'] ?? '0 GB (0 MB)' }}</span></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:0 18px 16px 18px;">
                                            <p style="margin:0 0 6px 0; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Top domains (this device)</p>
                                            @if (count($device['top_visited_domains']) > 0)
                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#334155;">
                                                    @foreach ($device['top_visited_domains'] as $row)
                                                        <tr>
                                                            <td style="padding:4px 0; border-bottom:1px solid #f1f5f9;">{{ $row['domain'] }}</td>
                                                            <td style="padding:4px 0; border-bottom:1px solid #f1f5f9; text-align:right; color:#64748b; width:48px;">{{ $row['visits'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            @else
                                                <p style="margin:0; font-size:13px; color:#94a3b8;">No recorded visits in this period.</p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endforeach
                @endif

                <tr>
                    <td style="padding:8px 28px 8px 28px;">
                        <p style="margin:0 0 10px 0; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">Top bandwidth devices (GB / MB)</p>
                        @if (count($payload['bandwidth']['top_bandwidth_devices'] ?? []) > 0)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#334155;">
                                @foreach (($payload['bandwidth']['top_bandwidth_devices'] ?? []) as $bandwidthRow)
                                    <tr>
                                        <td style="padding:6px 0; border-bottom:1px solid #f1f5f9;">{{ $bandwidthRow['device_name'] }}</td>
                                        <td style="padding:6px 0; border-bottom:1px solid #f1f5f9; text-align:right; color:#64748b;">{{ $bandwidthRow['bytes_total_formatted'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p style="margin:0; font-size:13px; color:#94a3b8;">No bandwidth activity in this period.</p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 28px 20px 28px;">
                        <p style="margin:0 0 10px 0; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase; color:#64748b;">All devices — top domains</p>
                        @if (count($payload['top_visited_domains']) > 0)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#334155;">
                                @foreach ($payload['top_visited_domains'] as $domainRow)
                                    <tr>
                                        <td style="padding:6px 0; border-bottom:1px solid #f1f5f9;">{{ $domainRow['domain'] }}</td>
                                        <td style="padding:6px 0; border-bottom:1px solid #f1f5f9; text-align:right; color:#64748b; width:48px;">{{ $domainRow['visits'] }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p style="margin:0; font-size:13px; color:#94a3b8;">No domain activity in this period.</p>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 28px 28px 28px; border-top:1px solid #e2e8f0; background:#f8fafc;">
                        <p style="margin:0; font-size:12px; color:#64748b;">Generated {{ now()->setTimezone($payload['timezone'])->format('M d, Y H:i:s') }} ({{ $payload['timezone'] }})</p>
                        <p style="margin:12px 0 0 0;">
                            <a href="{{ $payload['dashboard_url'] }}" style="display:inline-block; padding:10px 18px; background-color:#2563eb; color:#ffffff; text-decoration:none; border-radius:6px; font-size:14px; font-weight:600;">Open dashboard</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
