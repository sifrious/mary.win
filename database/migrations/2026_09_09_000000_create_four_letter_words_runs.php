<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('four_letter_words_runs', function (Blueprint $table) {
            $table->id();
            $table->string('account_id', 255);
            $table->uuid('run_id');
            $table->string('rules_version', 64);
            $table->string('dictionary_version', 64);
            $table->json('submissions');
            $table->timestamps();
            $table->unique(['account_id', 'run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('four_letter_words_runs');
    }
};
