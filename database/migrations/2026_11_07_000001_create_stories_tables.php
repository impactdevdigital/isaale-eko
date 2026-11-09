<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editorial posts: Food Stories and Brand Updates (Figma 07.03–07.05).
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // food_story | brand_update
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable(); // Markdown
            $table->string('cover_image')->nullable();
            $table->string('author_name')->nullable();
            $table->unsignedSmallInteger('read_minutes')->nullable(); // null = worked out from the body
            $table->boolean('is_trending')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->string('status', 20)->default('draft')->index(); // draft | published
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        // Reader comments; nothing shows until staff approve it.
        Schema::create('story_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('email');
            $table->text('body');
            $table->string('status', 20)->default('pending')->index(); // pending | approved | rejected
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_comments');
        Schema::dropIfExists('stories');
    }
};
