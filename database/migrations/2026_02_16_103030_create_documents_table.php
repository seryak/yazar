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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('slug');
            $table->string('url');
            $table->json('meta');
            $table->text('content');
            $table->string('type');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['path', 'type']);
            $table->unique('url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
