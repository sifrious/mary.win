<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Moves the starter kit's local-credential schema to an external-only one.
     *
     * The scaffold shipped with passwords, reset tokens, and a unique email,
     * because it assumed this application would be its own authentication
     * authority. It is not: identity, verification, and recovery belong to the
     * external provider, and account identity belongs to Zahir. Keeping a
     * password column would leave a second authority in the schema, ready to be
     * used by accident.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('zahir_account_id', 30)->nullable()->unique()->after('id');
        });

        Schema::table('users', function (Blueprint $table) {
            // No password exists for an externally authenticated account.
            $table->string('password')->nullable()->change();
        });

        // Zahir's contract is explicit that equal emails never merge
        // identities, so two accounts may legitimately share an address. A
        // unique index would reject the second person's first sign-in.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });

        // Reset tokens are ephemeral by design and meaningless without a local
        // password. The table goes with the flow that produced it.
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Restore the old constraints without losing anyone: give every
        // passwordless row an unusable hash, and make duplicate emails unique.
        DB::table('users')->whereNull('password')->update(['password' => bcrypt(Str::random(48))]);

        $duplicates = DB::table('users')
            ->select('email')->groupBy('email')->havingRaw('count(*) > 1')->pluck('email');

        foreach ($duplicates as $email) {
            foreach (DB::table('users')->where('email', $email)->orderBy('id')->pluck('id')->skip(1) as $id) {
                DB::table('users')->where('id', $id)->update(['email' => $id.'+'.$email]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('email', 'users_email_unique');
            $table->string('password')->nullable(false)->change();
            $table->dropUnique(['zahir_account_id']);
            $table->dropColumn('zahir_account_id');
        });
    }
};
