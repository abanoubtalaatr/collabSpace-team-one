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
        if (! Schema::hasColumn('tasks', 'start_date')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('start_date')->nullable()->after('description');
            });
        }

        if (! Schema::hasColumn('tasks', 'due_date')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('due_date')->nullable()->after('start_date');
            });
        }

        if (! Schema::hasColumn('tasks', 'progress')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->smallInteger('progress')->default(0)->after('due_date');
            });
        }

        if (! Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('priority')->default('medium')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['priority', 'progress', 'due_date', 'start_date'] as $column) {
            if (Schema::hasColumn('tasks', $column)) {
                Schema::table('tasks', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
