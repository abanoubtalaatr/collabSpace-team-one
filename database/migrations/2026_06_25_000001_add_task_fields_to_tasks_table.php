<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('title')->nullable()->after('project_id');
            $table->date('start_date')->nullable()->after('description');
            $table->date('due_date')->nullable()->after('start_date');
            $table->unsignedTinyInteger('progress')->default(0)->after('due_date');
            $table->string('priority')->default('medium')->after('progress');
        });

        if (Schema::hasColumn('tasks', 'name')) {
            foreach (DB::table('tasks')->select('id', 'name')->get() as $task) {
                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['title' => $task->name]);
            }

            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tasks MODIFY title VARCHAR(255) NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('name')->nullable()->after('project_id');
        });

        foreach (DB::table('tasks')->select('id', 'title')->get() as $task) {
            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['name' => $task->title]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['title', 'start_date', 'due_date', 'progress', 'priority']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE tasks MODIFY name VARCHAR(255) NOT NULL');
        }
    }
};
