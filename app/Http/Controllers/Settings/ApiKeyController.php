<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Show the API keys settings page
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/ApiKeys');
    }

    /**
     * Update the user's OpenAI API key
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'openai_api_key' => ['required', 'string', 'min:20'],
        ]);

        $apiKey = $request->input('openai_api_key');

        // Validate the API key with OpenAI
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->get('https://api.openai.com/v1/models');

            if (! $response->successful()) {
                throw ValidationException::withMessages([
                    'openai_api_key' => ['The provided API key is invalid. Please check and try again.'],
                ]);
            }
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'openai_api_key' => ['Failed to validate the API key. Please ensure it\'s correct and try again.'],
            ]);
        }

        // Update the user's API key
        $request->user()->update([
            'openai_api_key' => $apiKey,
        ]);

        return redirect()->route('api-keys.edit')->with('success', 'API key updated successfully.');
    }

    /**
     * Delete the user's OpenAI API key
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->update([
            'openai_api_key' => null,
        ]);

        return redirect()->route('api-keys.edit')->with('success', 'API key deleted successfully.');
    }
}
