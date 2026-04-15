<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('email_verified_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->text('approval_rejection_note')->nullable()->after('rejected_at');
        });

        // Existing parent accounts keep access after this change.
        DB::table('users')->where('role', 'parent')->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'rejected_at', 'approval_rejection_note']);
        });
    }
};
