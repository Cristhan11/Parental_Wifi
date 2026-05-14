<?php

namespace App\Http\Middleware;

use App\Models\BlockedWebsite;
use App\Models\Device;
use App\Models\DeviceSchedule;
use App\Models\FlaggedWebsite;
use App\Models\Quiz;
use App\Models\ReportingRecipient;
use App\Models\User;
use App\Models\Video;
use App\Services\SecurityAuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditSensitiveAction
{
    /**
     * Routes already covered by auth security listeners or high-frequency noise.
     *
     * @var list<string>
     */
    private const SKIP_ROUTE_NAMES = [
        'logout',
        'verification.send',
        'verification.verify',
    ];

    public function __construct(
        private SecurityAuditLogger $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        if ($request->user() === null) {
            return $response;
        }

        $routeName = $request->route()?->getName();
        if ($routeName !== null && in_array($routeName, self::SKIP_ROUTE_NAMES, true)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $this->logger->recordSensitiveAction($request, array_merge(
            ['method' => $request->method()],
            $this->subjectMetadataFromRoute($request),
        ));

        return $response;
    }

    /**
     * @return array<string, int|string>
     */
    private function subjectMetadataFromRoute(Request $request): array
    {
        $route = $request->route();
        if ($route === null) {
            return [];
        }

        $meta = [];
        foreach ($route->parameters() as $value) {
            if (! $value instanceof Model) {
                continue;
            }

            if ($value instanceof Device) {
                $meta['device_id'] = $value->id;
                $meta['device_name'] = $value->name;

                continue;
            }

            if ($value instanceof User) {
                $meta['subject_user_id'] = $value->id;
                $meta['subject_user_name'] = $value->name;
                $meta['subject_user_email'] = $value->email;

                continue;
            }

            if ($value instanceof BlockedWebsite) {
                $meta['blocked_domain'] = (string) $value->domain;

                continue;
            }

            if ($value instanceof FlaggedWebsite) {
                $meta['flagged_domain'] = (string) $value->domain;

                continue;
            }

            if ($value instanceof DeviceSchedule) {
                $meta['schedule_id'] = $value->id;
                $meta['schedule_day'] = (string) $value->day_of_week;
                $meta['schedule_device_name'] = $value->device?->name ?? '';

                continue;
            }

            if ($value instanceof Quiz) {
                $meta['quiz_id'] = $value->id;
                $meta['quiz_title'] = (string) $value->title;

                continue;
            }

            if ($value instanceof Video) {
                $meta['video_id'] = $value->id;
                $meta['video_title'] = (string) $value->title;

                continue;
            }

            if ($value instanceof ReportingRecipient) {
                $meta['recipient_id'] = $value->id;
                $meta['recipient_email'] = (string) $value->email;
            }
        }

        return $meta;
    }
}
