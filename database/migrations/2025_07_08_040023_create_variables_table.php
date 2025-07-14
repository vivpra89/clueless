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
        Schema::create('variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('text'); // text, number, boolean, json
            $table->text('description')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_system')->default(false);
            $table->json('validation_rules')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('category');
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variables');
    }
};
