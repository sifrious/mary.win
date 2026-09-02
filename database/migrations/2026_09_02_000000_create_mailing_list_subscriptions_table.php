<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_list_subscriptions', function (Blueprint $table) {
            $table->id();

            // Normalized (lowercased, trimmed) address. Unique so repeated
            // submission of the same address is idempotent.
            $table->string('email')->unique();

            // pending -> awaiting confirmation, subscribed -> confirmed opt-in,
            // unsubscribed -> opted out and excluded from sends.
            $table->string('status')->default('pending')->index();

            $table->string('source')->nullable();

            // Consent evidence: what was agreed to, when, and from where.
            $table->text('consent_text');
            $table->timestamp('consent_at');
            $table->string('consent_ip', 45)->nullable();

            // The confirmation token is stored as a SHA-256 digest so a database
            // leak does not hand out working confirmation links. Unsubscribe
            // links need no stored secret: they are signed URLs.
            $table->string('confirmation_token_hash', 64)->nullable()->unique();
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('confirmation_expires_at')->nullable();

            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();

            // Downstream provider mirror state. A failure here never discards
            // the local record; it is left visible for retry.
            $table->timestamp('provider_synced_at')->nullable();
            $table->timestamp('provider_sync_failed_at')->nullable();
            $table->text('provider_sync_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_list_subscriptions');
    }
};
