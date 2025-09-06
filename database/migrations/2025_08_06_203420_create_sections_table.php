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
            $table->boolean('is_published')->default(false); // Peut être gardé pour un contrôle global
            $table->enum('type', ['one_column', 'two_columns', 'tree_columns', 'for_columns', 'hero', 'gallery'])
                ->default('one_column');
            $table->string('color')->default('#ffffff');
            $table->string('slug')->nullable()->unique();
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->timestamps();
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
