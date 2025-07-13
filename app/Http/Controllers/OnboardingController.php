<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding page
     */
    public function show(Request $request): Response|RedirectResponse
    {
        // If user has already completed onboarding, redirect to dashboard
        if ($request->user()->has_completed_onboarding) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/Welcome');
    }

    /**
     * Store the OpenAI API key and complete onboarding
     */
    public function store(Request $request): RedirectResponse
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

        // Save the API key and mark onboarding as complete
        $request->user()->update([
            'openai_api_key' => $apiKey,
            'has_completed_onboarding' => true,
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome! Your OpenAI API key has been saved successfully.');
    }

    /**
     * Skip onboarding (user will use env variable)
     */
    public function skip(Request $request): RedirectResponse
    {
        // Check if there's an API key in the environment
        if (empty(config('openai.api_key'))) {
            return back()->with('error', 'No API key found in environment. Please provide your own API key.');
        }

        // Mark onboarding as complete without storing a personal API key
        $request->user()->update([
            'has_completed_onboarding' => true,
        ]);

        return redirect()->route('dashboard')->with('info', 'Using system default API key.');
    }
}
