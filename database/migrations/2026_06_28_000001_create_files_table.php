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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('original_name');
            $table->string('file_name');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->string('extension', 20);
            $table->string('file_type');
            $table->unsignedBigInteger('size');
            $table->string('status')->default('detached');
            $table->nullableMorphs('attachable');
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id', 'status']);
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
