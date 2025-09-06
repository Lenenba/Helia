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
            $table->text('excerpt')->nullable();
            $table->boolean('is_published')->default(false);

            // Le champ 'status' est plus flexible qu'un simple booléen 'is_published'
            $table->string('status')->default('draft');

            $table->string('type')->default('page');
            $table->timestamp('published_at')->nullable();
            $table->json('settings')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('pages')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();

            // Ajout d'index pour améliorer les performances des requêtes
            $table->index('status');
            $table->index('type');
        });

        // Table pivot pour lier les sections réutilisables aux pages
        Schema::create('page_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            // Garantit que l'ordre des sections est unique pour chaque page
            $table->unique(['page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_section');
        Schema::dropIfExists('pages');
    }
};
