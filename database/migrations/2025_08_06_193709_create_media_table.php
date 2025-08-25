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
            // Polymorphic relation fields
            $table->unsignedBigInteger('mediaable_id');
            $table->string('mediaable_type');

            // Allows grouping (avatar, garde, ...)
            $table->string('collection_name');
            $table->boolean('is_profile_picture')->default(false);
            // File info
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable(); // in bytes
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Optional JSON for extra metadata
            $table->json('custom_properties')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for faster lookups
            $table->index(['mediaable_type', 'mediaable_id']);
            $table->index('collection_name');
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
