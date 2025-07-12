<?php

namespace App\Services;

use App\Models\Variable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VariableService
{
    /**
     * Cache key prefix for variables
     */
    private const CACHE_PREFIX = 'variables:';
    
    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get all variables
     */
    public function getAll(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'all', self::CACHE_TTL, function () {
            return Variable::orderBy('category')->orderBy('key')->get();
        });
    }

    /**
     * Get variables by category
     */
    public function getByCategory(string $category): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . "category:{$category}", self::CACHE_TTL, function () use ($category) {
            return Variable::byCategory($category)->orderBy('key')->get();
        });
    }

    /**
     * Get a single variable by key
     */
    public function getByKey(string $key): ?Variable
    {
        return Cache::remember(self::CACHE_PREFIX . "key:{$key}", self::CACHE_TTL, function () use ($key) {
            return Variable::where('key', $key)->first();
        });
    }

    /**
     * Create or update a variable
     */
    public function upsert(array $data): Variable
    {
        // Validate the data
        $validated = validator($data, [
            'key' => 'required|string|max:255',
            'value' => 'required',
            'type' => 'required|in:text,number,boolean,json',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'is_system' => 'boolean',
            'validation_rules' => 'nullable|array',
        ])->validate();

        $variable = DB::transaction(function () use ($validated) {
            $variable = Variable::updateOrCreate(
                ['key' => $validated['key']],
                $validated
            );

            // Clear related caches
            $this->clearCache($variable);

            return $variable;
        });

        return $variable;
    }

    /**
     * Delete a variable
     */
    public function delete(string $key): bool
    {
        $variable = Variable::where('key', $key)->first();
        
        if (!$variable) {
            return false;
        }

        // Prevent deletion of system variables
        if ($variable->is_system) {
            throw new \Exception('Cannot delete system variables');
        }

        DB::transaction(function () use ($variable) {
            $variable->delete();
            $this->clearCache($variable);
        });

        return true;
    }

    /**
     * Replace variables in text
     */
    public function replaceInText(string $text, array $overrides = []): string
    {
        // Get all variables as key-value pairs
        $variables = $this->getVariablesAsArray();
        
        // Merge with overrides (overrides take precedence)
        $variables = array_merge($variables, $overrides);

        // Replace variables in the text
        foreach ($variables as $key => $value) {
            // Handle different value types
            $replacement = match (true) {
                is_array($value) => json_encode($value),
                is_bool($value) => $value ? 'true' : 'false',
                default => (string) $value,
            };

            // Replace {key} pattern
            $text = str_replace('{' . $key . '}', $replacement, $text);
        }

        return $text;
    }

    /**
     * Validate a variable value
     */
    public function validate(string $key, $value): bool
    {
        $variable = $this->getByKey($key);
        
        if (!$variable) {
            return false;
        }

        // Set the value temporarily for validation
        $variable->value = $value;
        
        return $variable->validate();
    }

    /**
     * Export all variables
     */
    public function export(): array
    {
        $variables = Variable::all();
        
        return [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'variables' => $variables->map(function ($variable) {
                return [
                    'key' => $variable->key,
                    'value' => $variable->value,
                    'type' => $variable->type,
                    'description' => $variable->description,
                    'category' => $variable->category,
                    'is_system' => $variable->is_system,
                    'validation_rules' => $variable->validation_rules,
                ];
            })->toArray(),
        ];
    }

    /**
     * Import variables
     */
    public function import(array $data): void
    {
        // Validate import data structure
        validator($data, [
            'version' => 'required|string',
            'variables' => 'required|array',
            'variables.*.key' => 'required|string',
            'variables.*.value' => 'required',
            'variables.*.type' => 'required|in:text,number,boolean,json',
            'variables.*.category' => 'required|string',
        ])->validate();

        DB::transaction(function () use ($data) {
            foreach ($data['variables'] as $variableData) {
                // Skip system variables unless explicitly allowed
                if (($variableData['is_system'] ?? false) && !config('app.allow_system_variable_import', false)) {
                    continue;
                }

                $this->upsert($variableData);
            }
        });

        // Clear all caches
        Cache::flush();
    }

    /**
     * Get all variables as a key-value array
     */
    public function getVariablesAsArray(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'array', self::CACHE_TTL, function () {
            return Variable::all()->pluck('typed_value', 'key')->toArray();
        });
    }

    /**
     * Get available categories
     */
    public function getCategories(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'categories', self::CACHE_TTL, function () {
            return Variable::distinct('category')->pluck('category')->sort();
        });
    }

    /**
     * Clear cache for a variable
     */
    private function clearCache(?Variable $variable = null): void
    {
        // Clear all variable caches
        Cache::forget(self::CACHE_PREFIX . 'all');
        Cache::forget(self::CACHE_PREFIX . 'array');
        Cache::forget(self::CACHE_PREFIX . 'categories');
        
        if ($variable) {
            Cache::forget(self::CACHE_PREFIX . "key:{$variable->key}");
            Cache::forget(self::CACHE_PREFIX . "category:{$variable->category}");
        }
    }

    /**
     * Seed default variables
     */
    public function seedDefaults(): void
    {
        $defaults = [
            [
                'key' => 'product_name',
                'value' => 'Your Product',
                'type' => 'text',
                'description' => 'The name of your product or service',
                'category' => 'product',
                'is_system' => true,
            ],
            [
                'key' => 'product_description',
                'value' => 'Product description here',
                'type' => 'text',
                'description' => 'A brief description of your product',
                'category' => 'product',
                'is_system' => true,
            ],
            [
                'key' => 'company_name',
                'value' => 'Your Company',
                'type' => 'text',
                'description' => 'Your company name',
                'category' => 'company',
                'is_system' => true,
            ],
            [
                'key' => 'pricing_starter',
                'value' => '$99/month',
                'type' => 'text',
                'description' => 'Starter tier pricing',
                'category' => 'pricing',
                'is_system' => true,
            ],
            [
                'key' => 'pricing_pro',
                'value' => '$299/month',
                'type' => 'text',
                'description' => 'Professional tier pricing',
                'category' => 'pricing',
                'is_system' => true,
            ],
            [
                'key' => 'pricing_enterprise',
                'value' => 'Custom',
                'type' => 'text',
                'description' => 'Enterprise tier pricing',
                'category' => 'pricing',
                'is_system' => true,
            ],
            [
                'key' => 'key_features',
                'value' => 'Feature 1, Feature 2, Feature 3',
                'type' => 'text',
                'description' => 'Comma-separated list of key features',
                'category' => 'product',
                'is_system' => true,
            ],
            [
                'key' => 'support_email',
                'value' => 'support@company.com',
                'type' => 'text',
                'description' => 'Support email address',
                'category' => 'support',
                'is_system' => true,
                'validation_rules' => ['email'],
            ],
            [
                'key' => 'support_phone',
                'value' => '1-800-XXX-XXXX',
                'type' => 'text',
                'description' => 'Support phone number',
                'category' => 'support',
                'is_system' => true,
            ],
        ];

        foreach ($defaults as $default) {
            Variable::firstOrCreate(
                ['key' => $default['key']],
                $default
            );
        }
    }
}