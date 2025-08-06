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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->string('type');            // image, video, file, audio, etc.
            $table->string('disk')->default('public'); // Storage disk utilisé (public, s3, local, etc.)
            $table->string('filename');        // Nom du fichier (ex: 32423.png)
            $table->string('original_name');   // Nom original à l'upload (ex: photo-vacances.png)
            $table->string('mime_type');       // ex: image/png
            $table->unsignedBigInteger('size'); // Taille en octets
            $table->string('path');            // Chemin complet ou relatif
            $table->string('url')->nullable(); // URL accessible publiquement (optionnel)
            $table->json('meta')->nullable();  // Pour stocker d'autres infos (dimensions, alt, EXIF, etc.)
            $table->boolean('is_public')->default(true); // Fichier visible par tous ou non

            // Optionnel pour les médias liés à des modèles (polymorphisme)
            // $table->nullableMorphs('mediable');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
