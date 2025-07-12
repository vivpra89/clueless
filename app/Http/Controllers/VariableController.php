<?php

namespace App\Http\Controllers;

use App\Services\VariableService;
use App\Services\TemplateVariableResolver;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class VariableController extends Controller
{
    private VariableService $variableService;
    private TemplateVariableResolver $templateResolver;

    public function __construct(VariableService $variableService, TemplateVariableResolver $templateResolver)
    {
        $this->variableService = $variableService;
        $this->templateResolver = $templateResolver;
    }

    /**
     * Display a listing of variables
     */
    public function index(Request $request)
    {
        $category = $request->get('category');
        
        $variables = $category 
            ? $this->variableService->getByCategory($category) 
            : $this->variableService->getAll();
            
        $categories = $this->variableService->getCategories();

        if ($request->wantsJson()) {
            return response()->json([
                'variables' => $variables,
                'categories' => $categories,
            ]);
        }

        return Inertia::render('Variables/Index', [
            'variables' => $variables,
            'categories' => $categories,
            'selectedCategory' => $category,
        ]);
    }

    /**
     * Store a newly created variable
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $variable = $this->variableService->upsert($request->all());
            
            return response()->json([
                'message' => 'Variable created successfully',
                'variable' => $variable,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create variable',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified variable
     */
    public function update(Request $request, string $key): JsonResponse
    {
        try {
            $data = array_merge($request->all(), ['key' => $key]);
            $variable = $this->variableService->upsert($data);
            
            return response()->json([
                'message' => 'Variable updated successfully',
                'variable' => $variable,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update variable',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified variable
     */
    public function destroy(string $key): JsonResponse
    {
        try {
            $deleted = $this->variableService->delete($key);
            
            if (!$deleted) {
                return response()->json([
                    'message' => 'Variable not found',
                ], 404);
            }
            
            return response()->json([
                'message' => 'Variable deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete variable',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import variables from JSON
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $content = json_decode($request->file('file')->get(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON file');
            }
            
            $this->variableService->import($content);
            
            return response()->json([
                'message' => 'Variables imported successfully',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Import validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to import variables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export all variables
     */
    public function export(): JsonResponse
    {
        try {
            $data = $this->variableService->export();
            
            return response()->json($data)
                ->header('Content-Disposition', 'attachment; filename="variables-export-' . now()->format('Y-m-d') . '.json"');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export variables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview text with variable replacement
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string',
            'overrides' => 'nullable|array',
        ]);

        try {
            $resolvedText = $this->variableService->replaceInText(
                $request->input('text'),
                $request->input('overrides', [])
            );
            
            $extractedVariables = $this->templateResolver->extractVariables($request->input('text'));
            
            return response()->json([
                'original' => $request->input('text'),
                'resolved' => $resolvedText,
                'variables_used' => $extractedVariables,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to preview text',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all variables used in templates
     */
    public function usage(): JsonResponse
    {
        try {
            $usage = $this->templateResolver->getAllUsedVariables();
            
            return response()->json([
                'usage' => $usage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get variable usage',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seed default variables
     */
    public function seedDefaults(): JsonResponse
    {
        try {
            $this->variableService->seedDefaults();
            
            return response()->json([
                'message' => 'Default variables seeded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to seed default variables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}