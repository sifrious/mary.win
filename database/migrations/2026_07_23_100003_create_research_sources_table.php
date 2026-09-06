<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base `research_sources` table (+ its two pivots) adopted from landing.
 * Guarded per table. `author` stays a denormalized display cache; the
 * normalized author graph rides `people` + `person_research_source`. The
 * per-talk S# tag lives on `research_source_talk.source_tag_local`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('research_sources')) {
            Schema::create('research_sources', function (Blueprint $table) {
                $table->id();
                $table->string('source_tag', 16)->nullable(); // canonical "S17"
                $table->string('title');
                $table->string('author')->nullable();
                $table->string('type', 16)->default('doc'); // podcast|talk|article|github|doc|book|chat
                $table->string('url', 2048)->nullable();
                $table->date('date_published')->nullable();
                $table->dateTime('retrieved_at')->nullable();
                $table->string('retrieval_method')->nullable();
                $table->string('artifact_status', 24)->default('unknown'); // full-text|partial|show-notes-only|unavailable|unknown
                $table->string('vault_path')->unique(); // upsert key
                $table->text('summary')->nullable();
                $table->mediumText('body_markdown')->nullable();
                $table->unsignedInteger('body_chars')->default(0);
                $table->string('body_hash', 64)->nullable();
                $table->timestamp('missing_from_vault_at')->nullable();
                $table->timestamps();

                $table->index('type');
                $table->index('artifact_status');
                $table->index('date_published');
            });
        }

        if (! Schema::hasTable('research_source_talk')) {
            Schema::create('research_source_talk', function (Blueprint $table) {
                $table->id();
                $table->foreignId('research_source_id')->constrained()->cascadeOnDelete();
                $table->foreignId('talk_id')->constrained()->cascadeOnDelete();
                $table->string('source_tag_local', 16)->nullable(); // per-talk S# namespace
                $table->timestamps();

                $table->unique(['research_source_id', 'talk_id']);
                $table->unique(['talk_id', 'source_tag_local']);
                $table->index('talk_id');
            });
        }

        if (! Schema::hasTable('research_source_topic')) {
            Schema::create('research_source_topic', function (Blueprint $table) {
                $table->id();
                $table->foreignId('research_source_id')->constrained()->cascadeOnDelete();
                $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['research_source_id', 'topic_id']);
                $table->index('topic_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('research_source_topic');
        Schema::dropIfExists('research_source_talk');
        Schema::dropIfExists('research_sources');
    }
};
