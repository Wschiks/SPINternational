<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('condition_type', 50)->nullable();
            $table->json('condition_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
