<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'job_title')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('job_title', 100)->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'exp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->smallInteger('exp')->default(0)->after('job_title');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('users', 'exp') ? 'exp' : null,
                Schema::hasColumn('users', 'job_title') ? 'job_title' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
