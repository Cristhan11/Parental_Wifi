<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Blocked websites are household-wide: one list per parent user, not per device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blocked_websites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        if (Schema::hasColumn('blocked_websites', 'device_id')) {
            DB::statement(
                'UPDATE blocked_websites SET user_id = (SELECT user_id FROM devices WHERE devices.id = blocked_websites.device_id)'
            );
        }

        DB::table('blocked_websites')->whereNull('user_id')->delete();

        $dupGroups = DB::table('blocked_websites')
            ->select('user_id', 'domain', 'block_type', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'domain', 'block_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupGroups as $row) {
            DB::table('blocked_websites')
                ->where('user_id', $row->user_id)
                ->where('domain', $row->domain)
                ->where('block_type', $row->block_type)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('blocked_websites', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropUnique('blocked_websites_device_domain_type_unique');
            $table->dropIndex(['device_id']);
            $table->dropColumn('device_id');
        });

        Schema::table('blocked_websites', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'domain', 'block_type'], 'blocked_websites_user_domain_type_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Rolling back blocked_websites user scope is not supported (data loss / ambiguous device assignment).'
        );
    }
};
