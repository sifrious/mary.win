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
        if (Schema::hasTable('repositories')) {
            return;
        }

        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('github_id');
            $table->string('name');
            $table->string('full_name');
            $table->text('description')->nullable();
            $table->boolean('private')->default(false);
            $table->string('url');
            $table->string('default_branch')->default('main');
            $table->timestamps();

            $table->unique(['user_id', 'github_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
