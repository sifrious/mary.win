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
        if (Schema::hasTable('repository_articles')) {
            return;
        }

        Schema::create('repository_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('repository_to_read_id')->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('filename');
            $table->string('file_extension')->nullable();
            $table->string('category')->nullable();
            $table->integer('size')->nullable();
            $table->string('sha')->nullable();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['user_id', 'repository_to_read_id']);
            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_articles');
    }
};
