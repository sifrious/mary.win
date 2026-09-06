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
        if (Schema::hasTable('repository_files')) {
            return;
        }

        Schema::create('repository_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('filename');
            $table->string('category')->index(); // readme, docs, config, code, other
            $table->bigInteger('size')->default(0); // file size in bytes
            $table->string('sha', 40); // git SHA hash
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['repository_id', 'category']);
            $table->index(['user_id', 'category']);
            $table->unique(['repository_id', 'path']); // Ensure no duplicate files per repo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_files');
    }
};
