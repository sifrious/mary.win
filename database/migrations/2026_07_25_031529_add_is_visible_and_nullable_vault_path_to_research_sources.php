<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adopted from clever/landing: a public-visibility switch on research sources,
 * plus a relaxed `vault_path` so sources with no vault artifact can be created.
 *
 * The clever version also backfills visibility from the `clever-comprehensive`
 * bibliography; mary.win carries no bibliography tables, so visibility is set
 * by the seeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('research_sources', 'is_visible')) {
            Schema::table('research_sources', function (Blueprint $table) {
                $table->boolean('is_visible')->default(false)->index()->after('type');
            });
        }

        Schema::table('research_sources', function (Blueprint $table) {
            $table->string('vault_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('research_sources', 'is_visible')) {
            Schema::table('research_sources', function (Blueprint $table) {
                $table->dropIndex(['is_visible']);
                $table->dropColumn('is_visible');
            });
        }

        Schema::table('research_sources', function (Blueprint $table) {
            $table->string('vault_path')->nullable(false)->change();
        });
    }
};
