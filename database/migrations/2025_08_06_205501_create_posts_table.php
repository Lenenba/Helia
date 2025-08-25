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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->string('excerpt')->nullable();
            $table->text('content');
            $table->foreignId('cover_media_id')->nullable()
                ->constrained('media')->nullOnDelete();
            $table->enum('image_position', ['left', 'right'])->default('left');
            $table->boolean('show_title')->default(true);
            $table->string('type')->default('post');
            $table->string('status')->default('draft');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('visibility')->default('public');
            $table->unsignedBigInteger('views')->default(0);
            $table->json('meta')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('posts')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();


            // Index utiles
            $table->index(['status', 'visibility']);
            $table->index(['type']);
            $table->index(['published_at']);
        });

        //tags
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Pivot tables for many-to-many relationships tags
        Schema::create('post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'tag_id']);
        });

        Schema::create('post_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'section_id']);
        });

        Schema::create('post_page', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'page_id']);
        });

        Schema::create('post_block', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_section');
        Schema::dropIfExists('post_page');
        Schema::dropIfExists('post_block');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
    }
};
