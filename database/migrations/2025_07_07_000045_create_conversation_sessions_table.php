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
        Schema::create('conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_company')->nullable();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('template_used')->nullable();
            
            // Customer Intelligence Summary
            $table->string('final_intent')->nullable();
            $table->string('final_buying_stage')->nullable();
            $table->integer('final_engagement_level')->default(50);
            $table->string('final_sentiment')->nullable();
            
            // Conversation Metrics
            $table->integer('total_transcripts')->default(0);
            $table->integer('total_insights')->default(0);
            $table->integer('total_topics')->default(0);
            $table->integer('total_commitments')->default(0);
            $table->integer('total_action_items')->default(0);
            
            // Summary and notes
            $table->text('ai_summary')->nullable();
            $table->text('user_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'started_at']);
            $table->index('customer_name');
            $table->index('customer_company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_sessions');
    }
};
