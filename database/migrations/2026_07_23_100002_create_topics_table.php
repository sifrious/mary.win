<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base `topics` table adopted from landing, extended with a nullable `talk_id`
 * ("topics are talk pages"). Handles all three shared-database states: absent
 * (create with the column), present-without-column (add it), present-with (skip).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('topics')) {
            Schema::create('topics', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->foreignId('talk_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('topics', 'talk_id')) {
            Schema::table('topics', function (Blueprint $table) {
                $table->foreignId('talk_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Undo only our own column addition; never drop a table landing may own.
     */
    public function down(): void
    {
        if (Schema::hasTable('topics') && Schema::hasColumn('topics', 'talk_id')) {
            Schema::table('topics', function (Blueprint $table) {
                $table->dropConstrainedForeignId('talk_id');
            });
        }
    }
};
