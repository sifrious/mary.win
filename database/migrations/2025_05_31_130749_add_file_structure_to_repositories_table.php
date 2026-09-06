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
        Schema::table('repositories', function (Blueprint $table) {
            $table->string('last_analyzed_commit_sha')->nullable()->after('is_active');
            $table->longText('file_structure')->nullable()->after('last_analyzed_commit_sha');
            $table->timestamp('file_structure_updated_at')->nullable()->after('file_structure');
            $table->integer('total_files_count')->default(0)->after('file_structure_updated_at');
            $table->integer('relevant_files_count')->default(0)->after('total_files_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repositories', function (Blueprint $table) {
            $table->dropColumn([
                'last_analyzed_commit_sha',
                'file_structure',
                'file_structure_updated_at',
                'total_files_count',
                'relevant_files_count',
            ]);
        });
    }
};
