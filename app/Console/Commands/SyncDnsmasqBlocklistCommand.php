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

    protected $description = 'Push blocked domains and DHCP DNS bypass (parent/guest/whitelist) from DB to dnsmasq (run on Pi after sudoers changes)';

    public function handle(DomainBlockingService $domainBlockingService): int
    {
        $id = (int) $this->argument('user_id');
        $user = User::query()->find($id);

        if (! $user) {
            $this->error("No user found with id {$id}.");

            return self::FAILURE;
        }

        $this->info("Syncing dnsmasq blocklist for user {$user->id} ({$user->email})...");

        $okBlocklist = $domainBlockingService->syncDnsmasqBlocklistForUser($user);
        $okBypass = $domainBlockingService->syncDnsmasqDhcpDnsBypassForUser($user);

        if (! $okBlocklist) {
            $this->error('Blocklist sync failed. Check storage/logs/laravel.log and sudoers for scripts/update_dnsmasq_global_blocklist.sh');

            return self::FAILURE;
        }

        $this->info('dnsmasq blocklist updated successfully.');

        if (! $okBypass) {
            $this->warn('DHCP DNS bypass sync failed. Check sudoers for scripts/update_dnsmasq_dhcp_dns_bypass.sh and laravel.log');

            return self::SUCCESS;
        }

        $this->info('dnsmasq DHCP DNS bypass updated successfully.');

        return self::SUCCESS;
    }
}
