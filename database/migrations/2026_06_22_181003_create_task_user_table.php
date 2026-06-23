<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_user', function (Blueprint $table) {
            $table->foreignId('task_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_user');
    }
};
