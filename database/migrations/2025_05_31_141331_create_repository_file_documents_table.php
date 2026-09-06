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
        if (Schema::hasTable('repository_file_documents')) {
            return;
        }

        Schema::create('repository_file_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_file_id')->constrained('repository_files')->onDelete('cascade');
            $table->foreignId('repository_id')->constrained()->onDelete('cascade');
            $table->longText('content');
            $table->timestamps();

            // Add indexes for better performance
            $table->index('repository_file_id');
            $table->index('repository_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_file_documents');
    }
};
