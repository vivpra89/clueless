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
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('context_snapshots');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate context_snapshots table
        Schema::create('context_snapshots', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['screenshot', 'audio', 'text']);
            $table->longText('content')->nullable();
            $table->json('metadata')->nullable();
            $table->binary('embedding')->nullable();
            $table->timestamps();
            $table->index('type');
            $table->index('created_at');
        });

        // Recreate conversations table
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
    }
};
