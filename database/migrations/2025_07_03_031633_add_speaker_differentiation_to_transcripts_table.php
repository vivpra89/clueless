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
        Schema::table('transcripts', function (Blueprint $table) {
            // Add columns for speaker differentiation
            $table->json('segments')->nullable()->after('transcript');
            $table->text('host_transcript')->nullable()->after('segments');
            $table->text('guest_transcript')->nullable()->after('host_transcript');
            $table->text('teleprompter_suggestions')->nullable()->after('summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transcripts', function (Blueprint $table) {
            $table->dropColumn(['segments', 'host_transcript', 'guest_transcript', 'teleprompter_suggestions']);
        });
    }
};
