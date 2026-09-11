<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GitHub tokens are stored encrypted, and the ciphertext is far longer than
     * the token: roughly 350 characters for a refresh token. These were created
     * as 255-character strings, which SQLite never enforces and PostgreSQL does,
     * so on PostgreSQL every GitHub sign-in failed writing the user row.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('github_token')->nullable()->change();
            $table->text('github_refresh_token')->nullable()->change();
        });
    }

    /**
     * Narrowing back to 255 characters fails on PostgreSQL once a real encrypted
     * token is stored. That failure is intended: it is better than truncating a
     * credential.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_token')->nullable()->change();
            $table->string('github_refresh_token')->nullable()->change();
        });
    }
};
