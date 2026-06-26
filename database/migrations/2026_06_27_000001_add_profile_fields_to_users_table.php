<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('country_code', 10)->nullable()->after('phone');
            $table->text('about')->nullable()->after('country_code');
            $table->string('availability_status')->default('available')->after('about');
            $table->foreignId('current_team_id')->nullable()->after('availability_status')->constrained('teams')->nullOnDelete();
            $table->foreignId('current_project_id')->nullable()->after('current_team_id')->constrained('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_project_id');
            $table->dropConstrainedForeignId('current_team_id');
            $table->dropColumn([
                'phone',
                'country_code',
                'about',
                'availability_status',
            ]);
        });
    }
};
