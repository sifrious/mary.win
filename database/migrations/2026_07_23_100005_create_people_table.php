<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalized people/authors and their (ordered, co-authorable) link to sources.
 * "Moseley & Marks" and "Kernighan & Plauger" become two rows each.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('people')) {
            Schema::create('people', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('sort_name')->nullable();
                $table->string('kind', 16)->default('person'); // person|org|pseudonym
                $table->text('bio')->nullable();
                $table->string('url', 2048)->nullable();
                $table->timestamps();

                $table->index('kind');
            });
        }

        if (! Schema::hasTable('person_research_source')) {
            Schema::create('person_research_source', function (Blueprint $table) {
                $table->id();
                $table->foreignId('person_id')->constrained()->cascadeOnDelete();
                $table->foreignId('research_source_id')->constrained()->cascadeOnDelete();
                $table->string('role', 16)->default('author'); // author|co_author|host|guest|editor
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['person_id', 'research_source_id', 'role']);
                $table->index('research_source_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('person_research_source');
        Schema::dropIfExists('people');
    }
};
