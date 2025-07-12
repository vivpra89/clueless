<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Facades\Log;

class TemplateVariableResolver
{
    private VariableService $variableService;

    public function __construct(VariableService $variableService)
    {
        $this->variableService = $variableService;
    }

    /**
     * Resolve variables in a template
     *
     * @param Template $template The template to resolve
     * @param array $contextVariables Context-specific variables that override defaults
     * @return array{prompt: string, variables: array, missing: array}
     */
    public function resolve(Template $template, array $contextVariables = []): array
    {
        // Get global variables
        $globalVariables = $this->variableService->getVariablesAsArray();
        
        // Get template-specific variables
        $templateVariables = $template->variables ?? [];
        
        // Merge variables (priority: context > template > global)
        $mergedVariables = array_merge($globalVariables, $templateVariables, $contextVariables);
        
        // Extract variable placeholders from the prompt
        $requiredVariables = $this->extractVariables($template->prompt);
        
        // Check for missing variables
        $missingVariables = array_diff($requiredVariables, array_keys($mergedVariables));
        
        // Replace variables in the prompt
        $resolvedPrompt = $this->variableService->replaceInText($template->prompt, $mergedVariables);
        
        // Also resolve variables in talking points if they exist
        $resolvedTalkingPoints = [];
        if (!empty($template->talking_points)) {
            foreach ($template->talking_points as $point) {
                $resolvedTalkingPoints[] = $this->variableService->replaceInText($point, $mergedVariables);
            }
        }
        
        // Log resolution details for debugging
        if (!empty($missingVariables)) {
            Log::warning('Missing template variables', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'missing' => $missingVariables,
            ]);
        }
        
        return [
            'prompt' => $resolvedPrompt,
            'variables' => $mergedVariables,
            'missing' => array_values($missingVariables),
            'talking_points' => $resolvedTalkingPoints,
        ];
    }

    /**
     * Extract variable placeholders from text
     *
     * @param string $text The text to extract variables from
     * @return array List of variable names found in the text
     */
    public function extractVariables(string $text): array
    {
        $variables = [];
        
        // Match {variable_name} pattern
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $text, $matches);
        
        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
        }
        
        return $variables;
    }

    /**
     * Preview how a template would look with current variables
     *
     * @param Template $template
     * @param array $overrides Variable overrides for preview
     * @return array
     */
    public function preview(Template $template, array $overrides = []): array
    {
        $result = $this->resolve($template, $overrides);
        
        // Add additional preview information
        $result['preview'] = [
            'original_prompt' => $template->prompt,
            'resolved_prompt' => $result['prompt'],
            'used_variables' => array_intersect_key(
                $result['variables'], 
                array_flip($this->extractVariables($template->prompt))
            ),
            'all_available_variables' => array_keys($result['variables']),
        ];
        
        return $result;
    }

    /**
     * Validate that a template has all required variables
     *
     * @param Template $template
     * @param array $providedVariables
     * @return bool
     */
    public function validateTemplate(Template $template, array $providedVariables = []): bool
    {
        $result = $this->resolve($template, $providedVariables);
        return empty($result['missing']);
    }

    /**
     * Get all variables used across all templates
     *
     * @return array
     */
    public function getAllUsedVariables(): array
    {
        $usedVariables = [];
        
        $templates = Template::all();
        foreach ($templates as $template) {
            $variables = $this->extractVariables($template->prompt);
            foreach ($variables as $var) {
                if (!isset($usedVariables[$var])) {
                    $usedVariables[$var] = [
                        'variable' => $var,
                        'used_in' => [],
                    ];
                }
                $usedVariables[$var]['used_in'][] = [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                ];
            }
        }
        
        return array_values($usedVariables);
    }

    /**
     * Suggest variables based on template content
     *
     * @param string $promptText
     * @return array
     */
    public function suggestVariables(string $promptText): array
    {
        $suggestions = [];
        
        // Common patterns to look for
        $patterns = [
            '/\$[\d,]+/' => 'pricing',
            '/\b(email|@)\b/i' => 'email',
            '/\b(phone|tel|call)\b/i' => 'phone',
            '/\b(product|service|solution)\b/i' => 'product',
            '/\b(company|business|organization)\b/i' => 'company',
            '/\b(feature|benefit|capability)\b/i' => 'feature',
            '/\b(support|help|assist)\b/i' => 'support',
        ];
        
        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $promptText)) {
                $suggestions[] = $type;
            }
        }
        
        // Get existing variables by suggested types
        $existingVariables = [];
        foreach (array_unique($suggestions) as $type) {
            $categoryVars = $this->variableService->getByCategory($type);
            foreach ($categoryVars as $var) {
                $existingVariables[] = [
                    'key' => $var->key,
                    'value' => $var->value,
                    'description' => $var->description,
                    'suggested_because' => $type,
                ];
            }
        }
        
        return $existingVariables;
    }
}