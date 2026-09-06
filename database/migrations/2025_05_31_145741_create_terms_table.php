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
        if (Schema::hasTable('terms')) {
            return;
        }

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('function_name')->index();
            $table->string('language')->nullable()->index();
            $table->string('framework')->nullable()->index();
            $table->string('implementation')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('library')->nullable()->index();
            $table->boolean('uses_callback')->default(false)->index();
            $table->timestamps();

            // Ensure unique combinations of function_name + language + framework + library
            $table->unique(['function_name', 'language', 'framework', 'library'], 'terms_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
