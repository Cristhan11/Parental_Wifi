<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flagged websites are household-wide: one list per parent user, not per device.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flagged_websites', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });

        if (Schema::hasColumn('flagged_websites', 'device_id')) {
            DB::statement(
                'UPDATE flagged_websites SET user_id = (SELECT user_id FROM devices WHERE devices.id = flagged_websites.device_id)'
            );
        }

        DB::table('flagged_websites')->whereNull('user_id')->delete();

        $dupGroups = DB::table('flagged_websites')
            ->select('user_id', 'domain', DB::raw('MIN(id) as keep_id'))
            ->groupBy('user_id', 'domain')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupGroups as $row) {
            DB::table('flagged_websites')
                ->where('user_id', $row->user_id)
                ->where('domain', $row->domain)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }

        Schema::table('flagged_websites', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropUnique(['device_id', 'domain']);
            $table->dropIndex(['device_id']);
            $table->dropColumn('device_id');
        });

        Schema::table('flagged_websites', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'domain'], 'flagged_websites_user_domain_unique');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Rolling back flagged_websites user scope is not supported (data loss / ambiguous device assignment).'
        );
    }
};
