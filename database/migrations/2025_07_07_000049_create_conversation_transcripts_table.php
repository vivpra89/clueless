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
        Schema::create('conversation_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('conversation_sessions')->onDelete('cascade');
            $table->enum('speaker', ['salesperson', 'customer', 'system']);
            $table->text('text');
            $table->timestamp('spoken_at');
            $table->string('group_id')->nullable(); // For grouping related messages
            $table->string('system_category')->nullable(); // For system messages: info, warning, success, error
            $table->integer('order_index'); // For maintaining order
            $table->timestamps();
            
            // Indexes
            $table->index(['session_id', 'spoken_at']);
            $table->index(['session_id', 'order_index']);
            $table->index('speaker');
            // Note: SQLite doesn't support fulltext indexes, would need to use Laravel Scout for search
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_transcripts');
    }
};
