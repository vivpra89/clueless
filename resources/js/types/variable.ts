export interface Variable {
    id: string;
    key: string;
    value: string;
    type: 'text' | 'number' | 'boolean' | 'json';
    description?: string;
    category: string;
    is_system: boolean;
    validation_rules?: Record<string, any>;
    created_at?: string;
    updated_at?: string;
}

export interface VariableCategory {
    name: string;
    count: number;
}

export interface VariableImportData {
    version: string;
    exported_at: string;
    variables: Omit<Variable, 'id' | 'created_at' | 'updated_at'>[];
}

export interface VariablePreview {
    original: string;
    resolved: string;
    variables_used: string[];
}

export interface VariableUsage {
    variable: string;
    used_in: Array<{
        template_id: string;
        template_name: string;
    }>;
}

export interface TemplateResolution {
    prompt: string;
    variables: Record<string, any>;
    missing: string[];
    talking_points?: string[];
}
