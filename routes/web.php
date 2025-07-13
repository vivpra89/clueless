<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'onboarding'])->name('dashboard');

// Onboarding Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [\App\Http\Controllers\OnboardingController::class, 'store'])->name('onboarding.store');
    Route::post('/onboarding/skip', [\App\Http\Controllers\OnboardingController::class, 'skip'])->name('onboarding.skip');
});

// NativePHP Desktop Routes
Route::get('/realtime-agent', function () {
    return Inertia::render('RealtimeAgent/Main');
})->name('realtime-agent');

Route::get('/realtime-agent/settings', function () {
    return Inertia::render('RealtimeAgent/Settings');
})->name('realtime-agent.settings');

// Realtime API Routes
Route::post('/api/realtime/session', [\App\Http\Controllers\RealtimeController::class, 'createSession'])
    ->name('realtime.session');
Route::post('/api/realtime/ephemeral-key', [\App\Http\Controllers\RealtimeController::class, 'generateEphemeralKey'])
    ->name('realtime.ephemeral-key');

// Template Routes
Route::get('/templates', [\App\Http\Controllers\TemplateController::class, 'index'])
    ->name('templates.index');
Route::post('/templates', [\App\Http\Controllers\TemplateController::class, 'store'])
    ->name('templates.store');
Route::put('/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'update'])
    ->name('templates.update');
Route::delete('/templates/{template}', [\App\Http\Controllers\TemplateController::class, 'destroy'])
    ->name('templates.destroy');
Route::post('/templates/{template}/use', [\App\Http\Controllers\TemplateController::class, 'incrementUsage'])
    ->name('templates.use');

// Template Form Routes
Route::get('/realtime-agent/templates/create', [\App\Http\Controllers\TemplateController::class, 'create'])
    ->name('templates.create');
Route::get('/realtime-agent/templates/{template}/edit', [\App\Http\Controllers\TemplateController::class, 'edit'])
    ->name('templates.edit');

// Conversation Routes (No auth required for desktop app)
Route::post('/conversations', [\App\Http\Controllers\ConversationController::class, 'store'])
    ->name('conversations.store');
Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index'])
    ->name('conversations.index');
Route::get('/conversations/{session}', [\App\Http\Controllers\ConversationController::class, 'show'])
    ->name('conversations.show');
Route::post('/conversations/{session}/end', [\App\Http\Controllers\ConversationController::class, 'end'])
    ->name('conversations.end');
Route::post('/conversations/{session}/transcript', [\App\Http\Controllers\ConversationController::class, 'saveTranscript'])
    ->name('conversations.transcript');
Route::post('/conversations/{session}/transcripts', [\App\Http\Controllers\ConversationController::class, 'saveBatchTranscripts'])
    ->name('conversations.transcripts');
Route::post('/conversations/{session}/insight', [\App\Http\Controllers\ConversationController::class, 'saveInsight'])
    ->name('conversations.insight');
Route::post('/conversations/{session}/insights', [\App\Http\Controllers\ConversationController::class, 'saveBatchInsights'])
    ->name('conversations.insights');
Route::patch('/conversations/{session}/notes', [\App\Http\Controllers\ConversationController::class, 'updateNotes'])
    ->name('conversations.notes');
Route::patch('/conversations/{session}/title', [\App\Http\Controllers\ConversationController::class, 'updateTitle'])
    ->name('conversations.title');
Route::delete('/conversations/{session}', [\App\Http\Controllers\ConversationController::class, 'destroy'])
    ->name('conversations.destroy');

// Variables Page Route
Route::get('/variables', function () {
    return Inertia::render('Variables/Index');
})->name('variables.page');

// Variable Management API Routes
Route::prefix('api/variables')->group(function () {
    Route::get('/', [\App\Http\Controllers\VariableController::class, 'index'])
        ->name('variables.index');
    Route::post('/', [\App\Http\Controllers\VariableController::class, 'store'])
        ->name('variables.store');
    Route::put('/{key}', [\App\Http\Controllers\VariableController::class, 'update'])
        ->name('variables.update');
    Route::delete('/{key}', [\App\Http\Controllers\VariableController::class, 'destroy'])
        ->name('variables.destroy');
    Route::post('/import', [\App\Http\Controllers\VariableController::class, 'import'])
        ->name('variables.import');
    Route::get('/export', [\App\Http\Controllers\VariableController::class, 'export'])
        ->name('variables.export');
    Route::post('/preview', [\App\Http\Controllers\VariableController::class, 'preview'])
        ->name('variables.preview');
    Route::get('/usage', [\App\Http\Controllers\VariableController::class, 'usage'])
        ->name('variables.usage');
    Route::post('/seed-defaults', [\App\Http\Controllers\VariableController::class, 'seedDefaults'])
        ->name('variables.seed');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
