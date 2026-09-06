<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base table adopted from the landing research library. Guarded so it is a
 * no-op when a shared database already provides it, and fully built on a blank
 * database. The shape mirrors landing exactly for shared-database compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('talks')) {
            return;
        }

        Schema::create('talks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('venue')->nullable();
            $table->date('event_date')->nullable();
            $table->text('thesis')->nullable();
            $table->string('vault_root')->nullable();
            $table->string('status', 24)->default('drafting'); // drafting|scheduled|delivered|archived
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Local/dev teardown only. On a shared database where landing owns this
     * table, do not roll back.
     */
    public function down(): void
    {
        Schema::dropIfExists('talks');
    }
};
