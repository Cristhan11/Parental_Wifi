<?php

namespace App\Support;

use App\Models\SecurityAuditEvent;

/**
 * Human-readable copy for the unified logs UI (parent-facing stream).
 */
final class ParentFriendlyLogSummaries
{
    /**
     * Append where the request came from, without implying the parent should interpret raw IPs on home traffic.
     */
    public static function appendSecurityAccessContext(string $baseSummary, SecurityAuditEvent $event): string
    {
        $suffix = $event->is_remote
            ? ' — Outside your home network (connection address '.$event->ip_address.')'
            : ' — From your home network';

        return $baseSummary.$suffix;
    }

    /**
     * Map audited route names to plain-language explanations.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function sensitiveActionSummary(?string $routeName, array $metadata): string
    {
        $route = $routeName ?? '';
        $deviceName = isset($metadata['device_name']) && is_string($metadata['device_name'])
            ? $metadata['device_name']
            : null;
        $forDevice = $deviceName !== null ? ' for '.$deviceName : '';

        $parentLabel = isset($metadata['subject_user_name']) && is_string($metadata['subject_user_name'])
            ? $metadata['subject_user_name']
            : (isset($metadata['subject_user_email']) && is_string($metadata['subject_user_email'])
                ? $metadata['subject_user_email']
                : null);
        $forParent = $parentLabel !== null ? ' ('.$parentLabel.')' : '';

        $domain = isset($metadata['blocked_domain']) && is_string($metadata['blocked_domain'])
            ? $metadata['blocked_domain']
            : (isset($metadata['flagged_domain']) && is_string($metadata['flagged_domain'])
                ? $metadata['flagged_domain']
                : null);
        $forDomain = $domain !== null ? ' — '.$domain : '';

        $quizTitle = isset($metadata['quiz_title']) && is_string($metadata['quiz_title'])
            ? $metadata['quiz_title']
            : null;
        $forQuiz = $quizTitle !== null ? ' — '.$quizTitle : '';

        $videoTitle = isset($metadata['video_title']) && is_string($metadata['video_title'])
            ? $metadata['video_title']
            : null;
        $forVideo = $videoTitle !== null ? ' — '.$videoTitle : '';

        $recipient = isset($metadata['recipient_email']) && is_string($metadata['recipient_email'])
            ? $metadata['recipient_email']
            : null;
        $forRecipient = $recipient !== null ? ' — '.$recipient : '';

        $scheduleDay = isset($metadata['schedule_day']) && is_string($metadata['schedule_day'])
            ? $metadata['schedule_day']
            : null;
        $scheduleDevice = isset($metadata['schedule_device_name']) && is_string($metadata['schedule_device_name'])
            ? $metadata['schedule_device_name']
            : null;
        $forSchedule = ($scheduleDevice !== null || $scheduleDay !== null)
            ? ' — '.trim(($scheduleDevice ?? 'Device').($scheduleDay !== null ? ', '.$scheduleDay : ''))
            : '';

        return match ($route) {
            'accounts.update' => 'Saved device details'.$forDevice.' (such as display name, daily screen time, Wi-Fi address, assigned quizzes or videos, or other device options)',
            'accounts.status.update' => 'Changed how internet access works'.$forDevice.' (normal time-limited access, fully blocked, or unlimited)',
            'accounts.time.update' => 'Adjusted screen time or time remaining'.$forDevice,
            'accounts.role.update' => 'Changed whether this device is treated as a child, parent, or guest'.$forDevice,
            'accounts.store' => 'Added a new device to your household',
            'accounts.destroy' => 'Removed a device from your household'.$forDevice,
            'accounts.registration-requests.approve' => 'Approved a device that asked to join your network',
            'accounts.registration-requests.reject' => 'Declined a device registration request',

            'profile.update' => 'Updated your profile (name or email)',
            'profile.destroy' => 'Started account deletion from profile settings',
            'profile.email-change.send-code' => 'Requested a code to change your sign-in email',
            'profile.email-change.verify-code' => 'Confirmed a new sign-in email with the verification code',
            'profile.tailscale.auth-link' => 'Requested a secure remote-access (Tailscale) sign-in link',

            'owner.onboarding.update' => 'Saved household owner setup answers',
            'password.force-change.update' => 'Set a new password after the app required an update',
            'verification.verify' => 'Verified your email address with the code from your inbox',

            'quizzes.store' => 'Created a new quiz'.$forQuiz,
            'quizzes.update' => 'Updated an existing quiz'.$forQuiz,
            'quizzes.destroy' => 'Deleted a quiz'.$forQuiz,
            'quizzes.import.process' => 'Imported quizzes from a file',
            'quizzes.import.pending.process' => 'Finished importing quizzes you started earlier',
            'quizzes.random-mode.update' => 'Changed random-quiz mode settings',

            'videos.store' => 'Added a new learning video'.$forVideo,
            'videos.update' => 'Updated a learning video'.$forVideo,
            'videos.destroy' => 'Removed a learning video'.$forVideo,

            'blocked-websites.store' => 'Added a site or app to the blocked list'.$forDomain,
            'blocked-websites.update' => 'Edited a blocked site or app rule'.$forDomain,
            'blocked-websites.destroy' => 'Removed something from the blocked list'.$forDomain,
            'blocked-websites.suggest-domains' => 'Asked the app to suggest related domains to block'.$forDomain,
            'blocked-websites.bulk-import' => 'Imported many blocked sites or apps at once',

            'flagged-websites.store' => 'Added a site to the watch list (flagged)'.$forDomain,
            'flagged-websites.update' => 'Edited a watch-list (flagged) site'.$forDomain,
            'flagged-websites.destroy' => 'Removed a site from the watch list'.$forDomain,

            'schedules.store' => 'Added an internet time rule'.$forSchedule,
            'schedules.update' => 'Changed an internet time rule'.$forSchedule,
            'schedules.destroy' => 'Deleted an internet time rule'.$forSchedule,

            'reports.preferences.update' => 'Updated email report preferences (what to include and how often)',
            'reports.recipients.bulk-save' => 'Saved several report email addresses at once',
            'reports.recipients.store' => 'Added an email address for report copies'.$forRecipient,
            'reports.recipients.update' => 'Changed a report email address or its on/off setting'.$forRecipient,
            'reports.recipients.destroy' => 'Removed a report email address'.$forRecipient,
            'reports.send-test-digest' => 'Sent a test copy of your email report',

            'admin.password-reset-requests.fulfill' => 'Admin: reset a parent password from a support request',
            'admin.parents.update' => 'Admin: edited a parent account'.$forParent,
            'admin.parents.destroy' => 'Admin: removed a parent account'.$forParent,
            'admin.parents.approve' => 'Admin: approved a new parent registration'.$forParent,
            'admin.parents.reject' => 'Admin: rejected a parent registration'.$forParent,
            'admin.parents.promote' => 'Admin: promoted a parent to household operator'.$forParent,
            'admin.parents.demote' => 'Admin: moved a household operator back to standard parent'.$forParent,
            'admin.parents.reset-password-default' => 'Admin: set a parent password back to the default'.$forParent,

            default => $route !== ''
                ? 'Saved an important change in the app (older entries may not list the exact screen)'
                : 'Saved an important change in the app',
        };
    }

    public static function deviceRowUpdatedSummary(string $deviceName): string
    {
        return 'Saved or synced details for '.$deviceName.' (for example name, screen time, Wi-Fi address, assigned learning, or internet access type—either you edited them or the system updated them).';
    }
}
