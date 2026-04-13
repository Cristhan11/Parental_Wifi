<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DomainBlockingService;
use Illuminate\Console\Command;

/**
 * Regenerate /etc/dnsmasq.d/parental-global-blocklist.conf from the database (Raspberry Pi).
 */
class SyncDnsmasqBlocklistCommand extends Command
{
    protected $signature = 'dnsmasq:sync-blocklist
                            {user_id : Parent account id (users.id) whose blocked_websites rows to apply}';

    protected $description = 'Push blocked domains from DB to dnsmasq (run on Pi as deploy/repair after sudoers changes)';

    public function handle(DomainBlockingService $domainBlockingService): int
    {
        $id = (int) $this->argument('user_id');
        $user = User::query()->find($id);

        if (! $user) {
            $this->error("No user found with id {$id}.");

            return self::FAILURE;
        }

        $this->info("Syncing dnsmasq blocklist for user {$user->id} ({$user->email})...");

        $ok = $domainBlockingService->syncDnsmasqBlocklistForUser($user);

        if (! $ok) {
            $this->error('Sync failed. Check storage/logs/laravel.log and sudoers for scripts/update_dnsmasq_global_blocklist.sh');

            return self::FAILURE;
        }

        $this->info('dnsmasq blocklist updated successfully.');

        return self::SUCCESS;
    }
}
