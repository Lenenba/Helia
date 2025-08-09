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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->boolean('is_published')->default(false);
            $table->text('type')->default('1 column');
            $table->string('color')->default('#ffffff');
            $table->unsignedInteger('order')->default(0); // Correction ici
            $table->string('slug')->nullable(); // Optionnel, pour URLs propres
            $table->json('settings')->nullable(); // Optionnel, flexibilité
            $table->foreignId('page_id')->constrained('pages')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();

            // Pour garantir que chaque section d'une page a un order unique
            $table->unique(['page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
