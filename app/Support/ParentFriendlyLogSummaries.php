<?php

namespace App\Support;

use App\Models\SecurityAuditEvent;

/**
 * Human-readable copy for the unified logs UI (parent-facing stream).
 */
final class ParentFriendlyLogSummaries
{
    /**
     * Short network context (full IP only when not home).
     */
    public static function appendSecurityAccessContext(string $baseSummary, SecurityAuditEvent $event): string
    {
        $suffix = $event->is_remote
            ? ' · Away ('.$event->ip_address.')'
            : ' · Home';

        return $baseSummary.$suffix;
    }

    /**
     * Map audited route names to plain-language explanations.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function sensitiveActionSummary(?string $routeName, array $metadata): string
    {
        if (isset($metadata['parent_summary']) && is_string($metadata['parent_summary']) && $metadata['parent_summary'] !== '') {
            return $metadata['parent_summary'];
        }

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
        $forDomain = $domain !== null ? ' · '.$domain : '';

        $quizTitle = isset($metadata['quiz_title']) && is_string($metadata['quiz_title'])
            ? $metadata['quiz_title']
            : null;
        $forQuiz = $quizTitle !== null ? ' · '.$quizTitle : '';

        $videoTitle = isset($metadata['video_title']) && is_string($metadata['video_title'])
            ? $metadata['video_title']
            : null;
        $forVideo = $videoTitle !== null ? ' · '.$videoTitle : '';

        $recipient = isset($metadata['recipient_email']) && is_string($metadata['recipient_email'])
            ? $metadata['recipient_email']
            : null;
        $forRecipient = $recipient !== null ? ' · '.$recipient : '';

        $scheduleDay = isset($metadata['schedule_day']) && is_string($metadata['schedule_day'])
            ? $metadata['schedule_day']
            : null;
        $scheduleDevice = isset($metadata['schedule_device_name']) && is_string($metadata['schedule_device_name'])
            ? $metadata['schedule_device_name']
            : null;
        $forSchedule = ($scheduleDevice !== null || $scheduleDay !== null)
            ? ' · '.trim(($scheduleDevice ?? 'Device').($scheduleDay !== null ? ', '.$scheduleDay : ''))
            : '';

        return match ($route) {
            'accounts.update' => 'Saved device settings'.$forDevice,
            'accounts.status.update' => 'Changed internet access'.$forDevice,
            'accounts.time.update' => 'Adjusted screen time'.$forDevice,
            'accounts.role.update' => 'Changed device role'.$forDevice,
            'accounts.store' => 'Added a device',
            'accounts.destroy' => 'Removed a device'.$forDevice,
            'accounts.registration-requests.approve' => 'Approved a device request',
            'accounts.registration-requests.reject' => 'Declined a device request',

            'profile.update' => 'Updated your profile',
            'profile.destroy' => 'Started account deletion',
            'profile.email-change.send-code' => 'Requested email change code',
            'profile.email-change.verify-code' => 'Verified new email',
            'profile.tailscale.auth-link' => 'Requested Tailscale access link',

            'owner.onboarding.update' => 'Saved owner onboarding',
            'password.force-change.update' => 'Updated required password',
            'verification.verify' => 'Verified your email',

            'quizzes.store' => 'Added a quiz'.$forQuiz,
            'quizzes.update' => 'Updated a quiz'.$forQuiz,
            'quizzes.destroy' => 'Deleted a quiz'.$forQuiz,
            'quizzes.import.process' => 'Imported quizzes from file',
            'quizzes.import.pending.process' => 'Finished quiz import',
            'quizzes.random-mode.update' => 'Updated random-quiz settings',

            'videos.store' => 'Added a video'.$forVideo,
            'videos.update' => 'Updated a video'.$forVideo,
            'videos.destroy' => 'Deleted a video'.$forVideo,

            'blocked-websites.store' => 'Blocked a site/app'.$forDomain,
            'blocked-websites.update' => 'Edited a block rule'.$forDomain,
            'blocked-websites.destroy' => 'Removed a block'.$forDomain,
            'blocked-websites.suggest-domains' => 'Suggested related domains'.$forDomain,
            'blocked-websites.bulk-import' => 'Bulk-imported blocks',

            'flagged-websites.store' => 'Flagged a site'.$forDomain,
            'flagged-websites.update' => 'Edited a flagged site'.$forDomain,
            'flagged-websites.destroy' => 'Removed a flag'.$forDomain,

            'schedules.store' => 'Added a schedule'.$forSchedule,
            'schedules.update' => 'Updated a schedule'.$forSchedule,
            'schedules.destroy' => 'Deleted a schedule'.$forSchedule,

            'reports.preferences.update' => 'Updated report preferences',
            'reports.recipients.bulk-save' => 'Saved report emails',
            'reports.recipients.store' => 'Added report email'.$forRecipient,
            'reports.recipients.update' => 'Updated report email'.$forRecipient,
            'reports.recipients.destroy' => 'Removed report email'.$forRecipient,
            'reports.send-test-digest' => 'Sent test report email',

            'admin.password-reset-requests.fulfill' => 'Admin reset parent password',
            'admin.parents.update' => 'Admin edited parent'.$forParent,
            'admin.parents.destroy' => 'Admin removed parent'.$forParent,
            'admin.parents.approve' => 'Admin approved parent'.$forParent,
            'admin.parents.reject' => 'Admin rejected parent'.$forParent,
            'admin.parents.promote' => 'Admin promoted parent'.$forParent,
            'admin.parents.demote' => 'Admin demoted parent'.$forParent,
            'admin.parents.reset-password-default' => 'Admin reset parent password'.$forParent,

            default => 'Saved a dashboard change',
        };
    }

    public static function deviceRowUpdatedSummary(string $deviceName): string
    {
        return 'Synced '.$deviceName.' (automatic update)';
    }
}
