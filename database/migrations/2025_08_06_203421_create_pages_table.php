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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable(); // Optionnel, résumé
            $table->boolean('is_published')->default(false);
            $table->string('type')->default('page'); // Pour support post/page/custom
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->json('settings')->nullable(); // Pour personnalisation future
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('pages')->onDelete('set null'); // Pour pages imbriquées
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
