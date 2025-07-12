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
        Schema::table('templates', function (Blueprint $table) {
            $table->uuid('id')->change();
            $table->text('description')->nullable()->after('name');
            $table->json('variables')->nullable()->after('prompt');
            $table->string('icon')->nullable()->after('category');
            $table->boolean('is_system')->default(false)->after('icon');
            $table->integer('usage_count')->default(0)->after('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['description', 'variables', 'icon', 'is_system', 'usage_count']);
        });
    }
};
