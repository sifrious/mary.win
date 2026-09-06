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
        if (Schema::hasTable('repository_file_terms')) {
            return;
        }

        Schema::create('repository_file_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_file_id')->constrained('repository_files')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('frequency')->default(1);
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['repository_file_id', 'term_id']);
            $table->index(['user_id', 'term_id']);
            $table->index('frequency');

            // Ensure unique combinations per file per term per user
            $table->unique(['repository_file_id', 'term_id', 'user_id'], 'file_term_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_file_terms');
    }
};
