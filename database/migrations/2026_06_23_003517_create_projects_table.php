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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->date('start_date');
            $table->date('deadline');
            $table->enum('priority', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['pending', 'in_progress', 'on_hold', 'completed', 'cancelled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
