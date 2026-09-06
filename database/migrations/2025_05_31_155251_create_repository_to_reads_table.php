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
        if (Schema::hasTable('repository_to_reads')) {
            return;
        }

        Schema::create('repository_to_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('github_id')->index();
            $table->string('name');
            $table->string('full_name');
            $table->text('description')->nullable();
            $table->boolean('private')->default(false);
            $table->string('url');
            $table->string('default_branch')->default('main');
            $table->string('owner');
            $table->integer('stargazers_count')->default(0);
            $table->integer('forks_count')->default(0);
            $table->string('language')->nullable();
            $table->timestamps();

            // Ensure user can't add the same repository twice
            $table->unique(['user_id', 'github_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_to_reads');
    }
};
