<?php

namespace App\Http\Controllers;

use App\Models\AccessAttempt;
use App\Models\BlockedWebsite;
use App\Models\BrowsingLog;
use App\Models\Device;
use App\Models\DeviceSchedule;
use App\Models\DeviceSession;
use App\Models\DeviceTimeGrant;
use App\Models\FlaggedWebsite;
use App\Models\ReportingPreference;
use App\Models\ReportingRecipientEvent;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * LogsController
 *
 * High-level responsibility:
 * - Provide one unified logs experience while preserving semantic separation
 *   between child activity and parent/admin changes.
 *
 * Why this controller matters:
 * - The product needs investigation-grade visibility (filters, sorting, paging, exports).
 * - The current data model stores log evidence across several domain tables, not one
 *   centralized audit table, so this controller normalizes those sources for UI/reporting.
 *
 * Architectural relevance:
 * - It acts as an adapter layer between domain models and a normalized log contract
 *   consumed by Blade and export endpoints.
 *
 * Relationship to email reporting:
 * - Digests aggregate some of the same sources ({@see \App\Services\ReportingDigestService}) for SMTP summaries;
 *   this controller is the interactive drill-down, not the mail pipeline.
 * - Parent/admin stream includes {@see ReportingRecipientEvent} (recipient add/edit/enable/remove) and
 *   {@see ReportingPreference} timestamps so email reporting setup appears alongside device/policy changes.
 */
class LogsController extends Controller
{
    /**
     * Render the unified logs page defined in the finals scope.
     *
     * Why this endpoint exists:
     * - The final requirements ask for two distinct log streams in one coherent UI:
     *   child activity vs parent/admin system changes.
     * - Users still need one filter surface, so we normalize both streams to one shape.
     *
     * What this endpoint is responsible for:
     * - Resolving scope (admin = global, parent = owned devices only)
     * - Building the selected stream dataset
     * - Applying the shared filters/sort/pagination behavior
     * - Returning stream counters so tabs reflect filtered counts accurately
     *
     * Relevance/connection:
     * - This is the core orchestration layer that connects the data-model sources
     *   to the `resources/views/logs/index.blade.php` page.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        // Guard stream input so unknown values cannot break routing/filter state.
        $stream = $request->input('stream', 'child_activity');
        if (! in_array($stream, ['child_activity', 'parent_admin_changes'], true)) {
            $stream = 'child_activity';
        }

        // Defaults intentionally match scope flow: show last 24 hours on first load.
        $from = $this->parseDateTime($request->input('from')) ?? now()->subDay();
        $to = $this->parseDateTime($request->input('to')) ?? now();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $deviceFilter = $request->filled('device_id') ? (int) $request->input('device_id') : null;
        $roleFilter = $request->input('role');
        $eventTypeFilter = $request->input('event_type');
        // Prefer `status`; keep `severity` as backward-compatible fallback for old links.
        $statusFilter = $request->input('status', $request->input('severity'));
        $keyword = trim((string) $request->input('keyword', ''));
        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = 20;

        $devices = $this->devicesForViewer($isAdmin);

        // Security + UX guard: ignore device filter values outside viewer scope.
        if ($deviceFilter !== null && ! $devices->contains('id', $deviceFilter)) {
            $deviceFilter = null;
        }

        // Build only the selected stream for the main table.
        $entries = $stream === 'child_activity'
            ? $this->buildChildActivityEntries($isAdmin, $from, $to, $deviceFilter)
            : $this->buildParentAdminEntries($isAdmin, $from, $to, $deviceFilter);

        // Apply the same filter semantics to both streams for consistent UX.
        $entries = $this->applySharedFilters($entries, $roleFilter, $eventTypeFilter, $statusFilter, $keyword);

        $entries = $sort === 'asc'
            ? $entries->sortBy(fn (array $row) => $row['timestamp']->timestamp)->values()
            : $entries->sortByDesc(fn (array $row) => $row['timestamp']->timestamp)->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginatedItems = $entries->slice(($page - 1) * $perPage, $perPage)->values();

        // We paginate in-memory because rows are merged from multiple sources/models.
        $logs = new LengthAwarePaginator(
            $paginatedItems,
            $entries->count(),
            $perPage,
            $page,
            [
                'path' => route('logs.index'),
                'query' => $request->query(),
            ]
        );

        // Counters are calculated using the same active filters to keep tab counts truthful.
        $streamCounts = [
            'child_activity' => $this->applySharedFilters(
                $this->buildChildActivityEntries($isAdmin, $from, $to, $deviceFilter),
                $roleFilter,
                $eventTypeFilter,
                $statusFilter,
                $keyword
            )->count(),
            'parent_admin_changes' => $this->applySharedFilters(
                $this->buildParentAdminEntries($isAdmin, $from, $to, $deviceFilter),
                $roleFilter,
                $eventTypeFilter,
                $statusFilter,
                $keyword
            )->count(),
        ];

        // View receives current filters so the UI can preserve state across actions.
        return view('logs.index', [
            'logs' => $logs,
            'stream' => $stream,
            'streamCounts' => $streamCounts,
            'devices' => $devices,
            'filters' => [
                'from' => $from->format('Y-m-d\TH:i'),
                'to' => $to->format('Y-m-d\TH:i'),
                'device_id' => $deviceFilter,
                'role' => $roleFilter,
                'event_type' => $eventTypeFilter,
                'status' => $statusFilter,
                'keyword' => $keyword,
                'sort' => $sort,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        // CSV remains the machine-friendly export option.
        // Why keep it:
        // - lightweight interoperability with scripts/BI tooling
        // - fast to generate for automation pipelines
        $entries = $this->resolveExportEntries($request);
        $stream = $request->input('stream', 'child_activity');
        if (! in_array($stream, ['child_activity', 'parent_admin_changes'], true)) {
            $stream = 'child_activity';
        }

        $filename = sprintf('logs-%s-%s.csv', $stream, now()->format('Ymd-His'));

        // Stream CSV to avoid high memory usage on large exports.
        return response()->streamDownload(function () use ($entries) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'timestamp',
                'type',
                'status',
                'role',
                'device',
                'target',
                'summary',
            ]);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry['timestamp']->format('M d, Y H:i:s'),
                    $entry['event_type'],
                    $entry['status'],
                    $entry['role'] ?: '',
                    $entry['device_name'] ?: '',
                    $entry['target'] ?: '',
                    $entry['summary'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        // Excel is the presentation-friendly export option.
        // Why this exists in addition to CSV:
        // - non-technical reviewers requested better readability
        // - status coloring and auto-sized columns reduce manual cleanup effort
        $entries = $this->resolveExportEntries($request);
        $stream = $request->input('stream', 'child_activity');
        if (! in_array($stream, ['child_activity', 'parent_admin_changes'], true)) {
            $stream = 'child_activity';
        }

        $filename = sprintf('logs-%s-%s.xlsx', $stream, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($entries) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Logs');

            $headers = ['timestamp', 'type', 'status', 'role', 'device', 'target', 'summary'];
            $sheet->fromArray($headers, null, 'A1');

            // Header styling intentionally mirrors dashboard color language for consistency.
            $sheet->getStyle('A1:G1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFDE15'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $row = 2;
            foreach ($entries as $entry) {
                $sheet->fromArray([
                    $entry['timestamp']->format('M d, Y H:i:s'),
                    $entry['event_type'],
                    $entry['status'],
                    $entry['role'] ?: '',
                    $entry['device_name'] ?: '',
                    $entry['target'] ?: '',
                    $entry['summary'],
                ], null, "A{$row}");

                $statusColor = match ($entry['status']) {
                    'critical' => 'FEE2E2',
                    'warning' => 'FEF3C7',
                    'success' => 'DCFCE7',
                    default => 'DBEAFE',
                };

                // Status tint gives immediate scanability without reading every row.
                $sheet->getStyle("C{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $statusColor],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $row++;
            }

            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Freeze header to keep context visible during long list review.
            $sheet->freezePane('A2');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveExportEntries(Request $request): Collection
    {
        /**
         * Shared export resolver used by CSV + Excel endpoints.
         *
         * Why this abstraction exists:
         * - Prevents drift between export formats.
         * - Ensures both exports always reflect the exact same filtered/sorted dataset.
         *
         * Practical outcome:
         * - If a row is visible in filtered UI logic, it is exported consistently.
         */
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $stream = $request->input('stream', 'child_activity');
        if (! in_array($stream, ['child_activity', 'parent_admin_changes'], true)) {
            $stream = 'child_activity';
        }

        $from = $this->parseDateTime($request->input('from')) ?? now()->subDay();
        $to = $this->parseDateTime($request->input('to')) ?? now();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $devices = $this->devicesForViewer($isAdmin);
        $deviceFilter = $request->filled('device_id') ? (int) $request->input('device_id') : null;
        if ($deviceFilter !== null && ! $devices->contains('id', $deviceFilter)) {
            $deviceFilter = null;
        }

        $entries = $stream === 'child_activity'
            ? $this->buildChildActivityEntries($isAdmin, $from, $to, $deviceFilter)
            : $this->buildParentAdminEntries($isAdmin, $from, $to, $deviceFilter);

        // Backward compatibility detail:
        // keep reading legacy `severity` links while standardizing on `status`.
        $entries = $this->applySharedFilters(
            $entries,
            $request->input('role'),
            $request->input('event_type'),
            $request->input('status', $request->input('severity')),
            trim((string) $request->input('keyword', ''))
        );

        $sort = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        return $sort === 'asc'
            ? $entries->sortBy(fn (array $row) => $row['timestamp']->timestamp)->values()
            : $entries->sortByDesc(fn (array $row) => $row['timestamp']->timestamp)->values();
    }

    private function devicesForViewer(bool $isAdmin): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        // Connection to role boundaries:
        // - Admin sees global scope.
        // - Parent sees only owned devices.
        // This aligns filters, table content, and export scope consistently.
        if ($isAdmin) {
            return Device::query()->orderBy('name')->get(['id', 'name']);
        }

        return $user->devices()->orderBy('name')->get(['id', 'name']);
    }

    private function buildChildActivityEntries(
        bool $isAdmin,
        Carbon $from,
        Carbon $to,
        ?int $deviceId
    ): Collection {
        /**
         * Assemble the child activity stream.
         *
         * Why this is multi-source:
         * - Child activity is not stored in one table in this project.
         * - We combine browsing, access attempts, time grants, and session transitions.
         *
         * Relevance:
         * - This fulfills the "child-device activity logs" scope while preserving
         *   backward compatibility with existing tables/jobs.
         */
        $entries = collect();

        $attempts = AccessAttempt::query()
            ->with('device.user')
            ->whereBetween('attempted_at', [$from, $to]);

        if (! $isAdmin) {
            $attempts->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $attempts->where('device_id', $deviceId);
        }

        // Access attempts represent security-focused child behavior.
        $entries = $entries->merge($attempts->get()->map(function (AccessAttempt $attempt): array {
            $isBlocked = $attempt->type === 'blocked_website';

            return [
                'id' => 'attempt-'.$attempt->id,
                'timestamp' => Carbon::parse($attempt->attempted_at),
                'stream' => 'child_activity',
                'event_type' => $isBlocked ? 'violation' : 'access-control',
                'status' => $isBlocked ? 'critical' : 'warning',
                'role' => $attempt->device?->role === 'child' ? 'child-device' : $attempt->device?->role,
                'device_id' => $attempt->device_id,
                'device_name' => $attempt->device?->name,
                'target' => $attempt->domain ?: $attempt->url,
                'summary' => $isBlocked
                    ? "Blocked website attempt: {$attempt->domain}"
                    : "Flagged website visited: {$attempt->domain}",
            ];
        }));

        $browsing = BrowsingLog::query()
            ->with('device.user')
            ->whereBetween('visited_at', [$from, $to]);

        if (! $isAdmin) {
            $browsing->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $browsing->where('device_id', $deviceId);
        }

        // Browsing logs provide baseline activity timeline context.
        $entries = $entries->merge($browsing->get()->map(function (BrowsingLog $log): array {
            return [
                'id' => 'browse-'.$log->id,
                'timestamp' => Carbon::parse($log->visited_at),
                'stream' => 'child_activity',
                'event_type' => 'access-control',
                'status' => 'info',
                'role' => $log->device?->role === 'child' ? 'child-device' : $log->device?->role,
                'device_id' => $log->device_id,
                'device_name' => $log->device?->name,
                'target' => $log->domain ?: $log->url,
                'summary' => "Website visited: {$log->domain}",
            ];
        }));

        $grants = DeviceTimeGrant::query()
            ->with('device.user')
            ->whereBetween('granted_at', [$from, $to]);

        if (! $isAdmin) {
            $grants->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $grants->where('device_id', $deviceId);
        }

        // Time grant records explain positive state transitions in timeline.
        $entries = $entries->merge($grants->get()->map(function (DeviceTimeGrant $grant): array {
            return [
                'id' => 'grant-'.$grant->id,
                'timestamp' => Carbon::parse($grant->granted_at),
                'stream' => 'child_activity',
                'event_type' => 'time-granted',
                'status' => 'success',
                'role' => $grant->device?->role === 'child' ? 'child-device' : $grant->device?->role,
                'device_id' => $grant->device_id,
                'device_name' => $grant->device?->name,
                'target' => $grant->source,
                'summary' => "Time granted: {$grant->minutes_granted} minute(s) via {$grant->source}",
            ];
        }));

        $connectedSessions = DeviceSession::query()
            ->with('device.user')
            ->whereBetween('started_at', [$from, $to]);

        if (! $isAdmin) {
            $connectedSessions->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $connectedSessions->where('device_id', $deviceId);
        }

        // Session rows provide connection/disconnection visibility.
        $entries = $entries->merge($connectedSessions->get()->map(function (DeviceSession $session): array {
            return [
                'id' => 'connect-'.$session->id,
                'timestamp' => Carbon::parse($session->started_at),
                'stream' => 'child_activity',
                'event_type' => 'connection',
                'status' => 'success',
                'role' => $session->device?->role === 'child' ? 'child-device' : $session->device?->role,
                'device_id' => $session->device_id,
                'device_name' => $session->device?->name,
                'target' => $session->device?->ip_address,
                'summary' => "Device connected: {$session->device?->name}",
            ];
        }));

        $disconnectedSessions = DeviceSession::query()
            ->with('device.user')
            ->whereNotNull('ended_at')
            ->whereBetween('ended_at', [$from, $to]);

        if (! $isAdmin) {
            $disconnectedSessions->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $disconnectedSessions->where('device_id', $deviceId);
        }

        return $entries->merge($disconnectedSessions->get()->map(function (DeviceSession $session): array {
            return [
                'id' => 'disconnect-'.$session->id,
                'timestamp' => Carbon::parse($session->ended_at),
                'stream' => 'child_activity',
                'event_type' => 'connection',
                'status' => 'info',
                'role' => $session->device?->role === 'child' ? 'child-device' : $session->device?->role,
                'device_id' => $session->device_id,
                'device_name' => $session->device?->name,
                'target' => $session->device?->ip_address,
                'summary' => "Device disconnected: {$session->device?->name}",
            ];
        }));
    }

    private function buildParentAdminEntries(
        bool $isAdmin,
        Carbon $from,
        Carbon $to,
        ?int $deviceId
    ): Collection {
        /**
         * Assemble parent/admin change stream from persisted configuration entities.
         *
         * Why derived entries:
         * - Most rows are projected from domain tables' timestamps.
         * - {@see SecurityAuditEvent} adds IP-aware auth and sensitive-action security events.
         *
         * Connection to scope:
         * - This gives immediate role-focused visibility for policy/config changes
         *   and creates a clean migration path to a future full audit trail.
         */
        $entries = collect();

        $blocked = BlockedWebsite::query()
            ->with('user')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('updated_at', [$from, $to]);
            });

        if (! $isAdmin) {
            $blocked->where('user_id', Auth::id());
        }
        if ($deviceId) {
            $filterDevice = Device::query()->find($deviceId);
            if ($filterDevice) {
                $blocked->where('user_id', $filterDevice->user_id);
            }
        }

        // Project CRUD timestamps into human-readable policy-change events.
        $entries = $entries->merge($blocked->get()->flatMap(function (BlockedWebsite $item) use ($from, $to): array {
            $rows = [];
            $actorRole = $item->user?->role;

            if ($item->created_at && Carbon::parse($item->created_at)->betweenIncluded($from, $to)) {
                $rows[] = [
                    'id' => 'blocked-create-'.$item->id,
                    'timestamp' => Carbon::parse($item->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'success',
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'All child devices',
                    'target' => $item->domain,
                    'summary' => 'Blocked website added (all child devices)',
                ];
            }

            if (
                $item->updated_at &&
                ! $item->updated_at->equalTo($item->created_at) &&
                Carbon::parse($item->updated_at)->betweenIncluded($from, $to)
            ) {
                $rows[] = [
                    'id' => 'blocked-update-'.$item->id,
                    'timestamp' => Carbon::parse($item->updated_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'info',
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'All child devices',
                    'target' => $item->domain,
                    'summary' => 'Blocked website updated (all child devices)',
                ];
            }

            return $rows;
        }));

        $flagged = FlaggedWebsite::query()
            ->with('user')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('updated_at', [$from, $to]);
            });

        if (! $isAdmin) {
            $flagged->where('user_id', Auth::id());
        }
        if ($deviceId) {
            $filterDevice = Device::query()->find($deviceId);
            if ($filterDevice) {
                $flagged->where('user_id', $filterDevice->user_id);
            }
        }

        $entries = $entries->merge($flagged->get()->flatMap(function (FlaggedWebsite $item) use ($from, $to): array {
            $rows = [];
            $actorRole = $item->user?->role;

            if ($item->created_at && Carbon::parse($item->created_at)->betweenIncluded($from, $to)) {
                $rows[] = [
                    'id' => 'flagged-create-'.$item->id,
                    'timestamp' => Carbon::parse($item->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'success',
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'All child devices',
                    'target' => $item->domain,
                    'summary' => 'Flagged website added (all child devices)',
                ];
            }

            if (
                $item->updated_at &&
                ! $item->updated_at->equalTo($item->created_at) &&
                Carbon::parse($item->updated_at)->betweenIncluded($from, $to)
            ) {
                $rows[] = [
                    'id' => 'flagged-update-'.$item->id,
                    'timestamp' => Carbon::parse($item->updated_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'info',
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'All child devices',
                    'target' => $item->domain,
                    'summary' => 'Flagged website updated (all child devices)',
                ];
            }

            return $rows;
        }));

        $schedules = DeviceSchedule::query()
            ->with('device.user')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('updated_at', [$from, $to]);
            });

        if (! $isAdmin) {
            $schedules->whereHas('device', fn ($q) => $q->where('user_id', Auth::id()));
        }
        if ($deviceId) {
            $schedules->where('device_id', $deviceId);
        }

        $entries = $entries->merge($schedules->get()->flatMap(function (DeviceSchedule $item) use ($from, $to): array {
            $rows = [];
            $actorRole = $item->device?->user?->role;

            if ($item->created_at && Carbon::parse($item->created_at)->betweenIncluded($from, $to)) {
                $rows[] = [
                    'id' => 'schedule-create-'.$item->id,
                    'timestamp' => Carbon::parse($item->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'success',
                    'role' => $actorRole,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device?->name,
                    'target' => $item->day_of_week,
                    'summary' => "Schedule created for {$item->device?->name} ({$item->day_of_week})",
                ];
            }

            if (
                $item->updated_at &&
                ! $item->updated_at->equalTo($item->created_at) &&
                Carbon::parse($item->updated_at)->betweenIncluded($from, $to)
            ) {
                $rows[] = [
                    'id' => 'schedule-update-'.$item->id,
                    'timestamp' => Carbon::parse($item->updated_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'policy-change',
                    'status' => 'info',
                    'role' => $actorRole,
                    'device_id' => $item->device_id,
                    'device_name' => $item->device?->name,
                    'target' => $item->day_of_week,
                    'summary' => "Schedule updated for {$item->device?->name} ({$item->day_of_week})",
                ];
            }

            return $rows;
        }));

        $devices = Device::query()
            ->with('user')
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [$from, $to])
                    ->orWhereBetween('updated_at', [$from, $to]);
            });

        if (! $isAdmin) {
            $devices->where('user_id', Auth::id());
        }
        if ($deviceId) {
            $devices->where('id', $deviceId);
        }

        $entries = $entries->merge($devices->get()->flatMap(function (Device $item) use ($from, $to): array {
            $rows = [];
            $actorRole = $item->user?->role;

            if ($item->created_at && Carbon::parse($item->created_at)->betweenIncluded($from, $to)) {
                $rows[] = [
                    'id' => 'device-create-'.$item->id,
                    'timestamp' => Carbon::parse($item->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'configuration',
                    'status' => 'success',
                    'role' => $actorRole,
                    'device_id' => $item->id,
                    'device_name' => $item->name,
                    'target' => $item->role,
                    'summary' => "Device created: {$item->name}",
                ];
            }

            if (
                $item->updated_at &&
                ! $item->updated_at->equalTo($item->created_at) &&
                Carbon::parse($item->updated_at)->betweenIncluded($from, $to)
            ) {
                $rows[] = [
                    'id' => 'device-update-'.$item->id,
                    'timestamp' => Carbon::parse($item->updated_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'configuration',
                    'status' => 'info',
                    'role' => $actorRole,
                    'device_id' => $item->id,
                    'device_name' => $item->name,
                    'target' => $item->role,
                    'summary' => "Device updated: {$item->name}",
                ];
            }

            return $rows;
        }));

        // Email reporting: recipients + preferences are account-level (no device_id).
        // When the UI filters by a single device, skip these rows — they are not tied to one device.
        if ($deviceId === null) {
            $recipientEvents = ReportingRecipientEvent::query()
                ->with('user')
                ->whereBetween('created_at', [$from, $to]);

            if (! $isAdmin) {
                $recipientEvents->where('user_id', Auth::id());
            }

            $entries = $entries->merge($recipientEvents->orderByDesc('created_at')->get()->map(function (ReportingRecipientEvent $event): array {
                $actorRole = $event->user?->role;
                $status = match ($event->action) {
                    'added' => 'success',
                    'removed' => 'warning',
                    default => 'info',
                };

                return [
                    'id' => 'reporting-recipient-event-'.$event->id,
                    'timestamp' => Carbon::parse($event->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'configuration',
                    'status' => $status,
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'Reporting',
                    'target' => $event->email,
                    'summary' => $event->summary,
                ];
            }));

            $reportingPreferences = ReportingPreference::query()
                ->with('user')
                ->where(function ($query) use ($from, $to) {
                    $query->whereBetween('created_at', [$from, $to])
                        ->orWhereBetween('updated_at', [$from, $to]);
                });

            if (! $isAdmin) {
                $reportingPreferences->where('user_id', Auth::id());
            }

            $entries = $entries->merge($reportingPreferences->get()->flatMap(function (ReportingPreference $item) use ($from, $to): array {
                $rows = [];
                $actorRole = $item->user?->role;

                if ($item->created_at && Carbon::parse($item->created_at)->betweenIncluded($from, $to)) {
                    $rows[] = [
                        'id' => 'reporting-prefs-create-'.$item->id,
                        'timestamp' => Carbon::parse($item->created_at),
                        'stream' => 'parent_admin_changes',
                        'event_type' => 'configuration',
                        'status' => 'success',
                        'role' => $actorRole,
                        'device_id' => null,
                        'device_name' => 'Reporting',
                        'target' => 'preferences',
                        'summary' => 'Reporting preferences initialized',
                    ];
                }

                if (
                    $item->updated_at &&
                    ! $item->updated_at->equalTo($item->created_at) &&
                    Carbon::parse($item->updated_at)->betweenIncluded($from, $to)
                ) {
                    $rows[] = [
                        'id' => 'reporting-prefs-update-'.$item->id,
                        'timestamp' => Carbon::parse($item->updated_at),
                        'stream' => 'parent_admin_changes',
                        'event_type' => 'configuration',
                        'status' => 'info',
                        'role' => $actorRole,
                        'device_id' => null,
                        'device_name' => 'Reporting',
                        'target' => 'preferences',
                        'summary' => 'Reporting preferences updated',
                    ];
                }

                return $rows;
            }));
        }

        if ($deviceId === null) {
            $security = SecurityAuditEvent::query()
                ->with('user')
                ->whereBetween('created_at', [$from, $to]);

            if (! $isAdmin) {
                $viewerEmail = Auth::user()->email;
                $security->where(function ($q) use ($viewerEmail): void {
                    $q->where('user_id', Auth::id())
                        ->orWhere('attempted_identifier', $viewerEmail);
                });
            }

            $entries = $entries->merge($security->orderByDesc('created_at')->get()->map(function (SecurityAuditEvent $e): array {
                $actorRole = $e->user?->role ?? 'guest';
                $status = match ($e->event) {
                    SecurityAuditEvent::EVENT_LOGIN_FAILURE => 'failed',
                    SecurityAuditEvent::EVENT_LOCKOUT => 'warning',
                    SecurityAuditEvent::EVENT_LOGIN_SUCCESS => 'success',
                    SecurityAuditEvent::EVENT_LOGOUT => 'info',
                    default => 'info',
                };
                $source = $e->is_remote ? 'remote' : 'local';
                $summary = match ($e->event) {
                    SecurityAuditEvent::EVENT_LOGIN_SUCCESS => 'Login succeeded',
                    SecurityAuditEvent::EVENT_LOGIN_FAILURE => 'Login failed'
                        .($e->attempted_identifier ? ' ('.$e->attempted_identifier.')' : ''),
                    SecurityAuditEvent::EVENT_LOGOUT => 'Logout',
                    SecurityAuditEvent::EVENT_LOCKOUT => 'Login temporarily locked (too many attempts)',
                    SecurityAuditEvent::EVENT_SENSITIVE_ACTION => 'Sensitive action: '.($e->route_name ?? 'unknown'),
                    default => $e->event,
                };
                $summary .= ' · IP '.$e->ip_address.' · '.$source;

                return [
                    'id' => 'security-'.$e->id,
                    'timestamp' => Carbon::parse($e->created_at),
                    'stream' => 'parent_admin_changes',
                    'event_type' => 'security-access',
                    'status' => $status,
                    'role' => $actorRole,
                    'device_id' => null,
                    'device_name' => 'Security',
                    'target' => $e->ip_address,
                    'summary' => $summary,
                ];
            }));
        }

        return $entries;
    }

    private function applySharedFilters(
        Collection $entries,
        ?string $roleFilter,
        ?string $eventTypeFilter,
        ?string $statusFilter,
        string $keyword
    ): Collection {
        // Shared filtering is centralized to keep:
        // - stream tabs
        // - table rows
        // - export rows
        // behaviorally consistent.
        if ($roleFilter) {
            $entries = $entries->filter(fn (array $row) => $row['role'] === $roleFilter);
        }

        if ($eventTypeFilter) {
            $entries = $entries->filter(fn (array $row) => $row['event_type'] === $eventTypeFilter);
        }

        if ($statusFilter) {
            $entries = $entries->filter(fn (array $row) => $row['status'] === $statusFilter);
        }

        if ($keyword !== '') {
            $needle = mb_strtolower($keyword);
            $entries = $entries->filter(function (array $row) use ($needle): bool {
                // Keyword search deliberately spans multiple fields to emulate
                // "investigation search" behavior (device + target + summary + type).
                $haystack = mb_strtolower(implode(' ', [
                    $row['summary'] ?? '',
                    $row['target'] ?? '',
                    $row['device_name'] ?? '',
                    $row['role'] ?? '',
                    $row['event_type'] ?? '',
                ]));

                return str_contains($haystack, $needle);
            });
        }

        return $entries->values();
    }

    private function parseDateTime(?string $value): ?Carbon
    {
        // Tolerant parser prevents hard failures on malformed query params.
        // Returning null triggers safe defaults in callers.
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
