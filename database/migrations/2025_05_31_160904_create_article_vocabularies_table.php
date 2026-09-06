<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('article_vocabularies')) {
            return;
        }

        Schema::create('article_vocabularies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('repository_to_read_id')->constrained()->onDelete('cascade');
            $table->foreignId('repository_article_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained()->onDelete('cascade');
            $table->integer('frequency')->default(1);
            $table->timestamps();

            // Ensure unique combination - no duplicate terms per file per user
            $table->unique(['user_id', 'repository_to_read_id', 'repository_article_id', 'term_id'], 'article_vocab_unique');

            // Indexes for efficient queries
            $table->index(['user_id', 'repository_to_read_id']);
            $table->index(['term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_vocabularies');
    }
};
