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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // e.g., 'text', 'image', etc.
            $table->string('title')->nullable(); // Optionnel, pour certains blocs
            $table->text('content'); // Main content
            $table->json('settings')->nullable(); // Paramètres dynamiques
            $table->boolean('is_published')->default(false);
            $table->string('status')->default('draft'); // Pour des workflows plus avancés
            $table->foreignId('media_id')->nullable()->constrained('media')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('block_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('order')->default(0); // Ordre dans la section
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('block_section');
    }
};
