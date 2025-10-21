<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_logs', function (Blueprint $t) {
            // rename user_id -> actor_id
            if (Schema::hasColumn('approval_logs', 'user_id')) {
                $t->renameColumn('user_id', 'actor_id');
            }

            // add actor_role & level jika belum ada
            if (!Schema::hasColumn('approval_logs', 'actor_role')) {
                $t->string('actor_role', 32)->nullable()->after('actor_id');
            }
            if (!Schema::hasColumn('approval_logs', 'level')) {
                $t->tinyInteger('level')->nullable()->after('action'); // 0=submission,1=wakadek,2=dekan
            }

            // index
            $t->index(['laporan_id','action','level'], 'idx_laporan_action_level');
        });
    }

    public function down(): void
    {
        Schema::table('approval_logs', function (Blueprint $t) {
            if (Schema::hasColumn('approval_logs', 'level')) {
                $t->dropIndex('idx_laporan_action_level');
                $t->dropColumn('level');
            }
            if (Schema::hasColumn('approval_logs', 'actor_role')) {
                $t->dropColumn('actor_role');
            }
            if (Schema::hasColumn('approval_logs', 'actor_id')) {
                $t->renameColumn('actor_id', 'user_id');
            }
        });
    }
};
