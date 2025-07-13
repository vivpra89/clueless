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
        Schema::create('conversation_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('conversation_sessions')->onDelete('cascade');
            $table->string('insight_type'); // topics, commitments, insights, action_items
            $table->json('data'); // Store the full object data
            $table->timestamp('captured_at');
            $table->timestamps();

            // Indexes
            $table->index(['session_id', 'insight_type']);
            $table->index(['session_id', 'captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_insights');
    }
};
