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
        if (Schema::hasColumn('tasks', 'title') && ! Schema::hasColumn('tasks', 'name')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->renameColumn('title', 'name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'name') && ! Schema::hasColumn('tasks', 'title')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->renameColumn('name', 'title');
            });
        }
    }
};
