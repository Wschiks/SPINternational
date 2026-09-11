<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login')->nullable()->after('email_verified_at');
            $table->unsignedInteger('total_score')->default(0);
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('games_won')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login', 'total_score', 'games_played', 'games_won']);
        });
    }
};
