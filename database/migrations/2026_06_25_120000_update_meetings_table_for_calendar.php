<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildMeetingsTableForSqlite();

            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('scheduled_at');
        });

        DB::statement('ALTER TABLE meetings MODIFY starts_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE meetings MODIFY ends_at DATETIME NOT NULL');

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('ends_at');
            $table->string('location')->nullable()->after('meeting_link');
            $table->foreignId('created_by')->after('location')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->after('created_by')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();
            Schema::dropIfExists('meeting_user');
            Schema::dropIfExists('meetings');

            Schema::create('meetings', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('scheduled_at');
                $table->time('starts_at');
                $table->time('ends_at');
                $table->timestamps();
            });

            Schema::create('meeting_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meeting_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            });

            Schema::enableForeignKeyConstraints();

            return;
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['meeting_link', 'location']);
        });

        DB::statement('ALTER TABLE meetings MODIFY starts_at TIME NOT NULL');
        DB::statement('ALTER TABLE meetings MODIFY ends_at TIME NOT NULL');

        Schema::table('meetings', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->after('description');
        });
    }

    private function rebuildMeetingsTableForSqlite(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('meeting_user');
        Schema::dropIfExists('meetings');

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('meeting_link')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('meeting_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
};
