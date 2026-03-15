<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('hash', 64)->unique();
            $table->string('path');
            $table->string('disk', 20)->default('local');
            $table->string('mime_type', 50);
            $table->string('original_extension', 10);
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('compressed_size')->nullable();
            $table->boolean('is_compressed')->default(false);
            $table->unsignedInteger('reference_count')->default(0);
            $table->timestamps();

            $table->index('hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
