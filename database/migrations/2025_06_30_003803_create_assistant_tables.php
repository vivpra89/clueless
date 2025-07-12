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
        // Settings table for app configuration
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Context snapshots for storing screen captures and audio transcripts
        Schema::create('context_snapshots', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['screenshot', 'audio', 'text']);
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $table->binary('embedding')->nullable(); // For future semantic search
            $table->timestamps();
            $table->index('type');
            $table->index('created_at');
        });

        // Conversations with the AI assistant
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->json('context_snapshot_ids')->nullable();
            $table->longText('user_message');
            $table->longText('assistant_response')->nullable();
            $table->string('model')->nullable();
            $table->string('provider')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        // Templates for quick actions
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('prompt');
            $table->string('shortcut')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('context_snapshots');
        Schema::dropIfExists('settings');
    }
};
