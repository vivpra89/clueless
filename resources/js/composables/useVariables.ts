import type { Variable, VariableCategory, VariableImportData, VariablePreview, VariableUsage } from '@/types/variable';
import axios from 'axios';
import { computed, ref } from 'vue';

export function useVariables() {
    const variables = ref<Variable[]>([]);
    const categories = ref<string[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Computed properties
    const variablesByCategory = computed(() => {
        const grouped: Record<string, Variable[]> = {};
        variables.value.forEach((variable) => {
            if (!grouped[variable.category]) {
                grouped[variable.category] = [];
            }
            grouped[variable.category].push(variable);
        });
        return grouped;
    });

    const categoryStats = computed((): VariableCategory[] => {
        return categories.value.map((category) => ({
            name: category,
            count: variablesByCategory.value[category]?.length || 0,
        }));
    });

    const systemVariables = computed(() => variables.value.filter((v) => v.is_system));

    const userVariables = computed(() => variables.value.filter((v) => !v.is_system));

    // API methods
    const loadVariables = async (category?: string) => {
        loading.value = true;
        error.value = null;

        try {
            const params = category ? { category } : {};
            const response = await axios.get('/api/variables', { params });
            variables.value = response.data.variables;
            categories.value = response.data.categories;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to load variables';
        } finally {
            loading.value = false;
        }
    };

    const saveVariable = async (variable: Partial<Variable>): Promise<Variable | null> => {
        loading.value = true;
        error.value = null;

        try {
            const isUpdate = variable.key && variables.value.some((v) => v.key === variable.key);
            const response = isUpdate ? await axios.put(`/api/variables/${variable.key}`, variable) : await axios.post('/api/variables', variable);

            await loadVariables(); // Reload to get updated list
            return response.data.variable;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to save variable';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const deleteVariable = async (key: string): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(`/api/variables/${key}`);
            await loadVariables(); // Reload to get updated list
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete variable';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const importVariables = async (file: File): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('file', file);

            await axios.post('/api/variables/import', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            await loadVariables(); // Reload to get updated list
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to import variables';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const exportVariables = async (): Promise<VariableImportData | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/variables/export');
            return response.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to export variables';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const previewText = async (text: string, overrides?: Record<string, any>): Promise<VariablePreview | null> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/variables/preview', {
                text,
                overrides,
            });
            return response.data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to preview text';
            return null;
        } finally {
            loading.value = false;
        }
    };

    const getVariableUsage = async (): Promise<VariableUsage[]> => {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/variables/usage');
            return response.data.usage;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to get variable usage';
            return [];
        } finally {
            loading.value = false;
        }
    };

    const seedDefaults = async (): Promise<boolean> => {
        loading.value = true;
        error.value = null;

        try {
            await axios.post('/api/variables/seed-defaults');
            await loadVariables(); // Reload to get updated list
            return true;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to seed default variables';
            return false;
        } finally {
            loading.value = false;
        }
    };

    // Helper methods
    const findVariable = (key: string): Variable | undefined => {
        return variables.value.find((v) => v.key === key);
    };

    const getVariableValue = (key: string, defaultValue?: any): any => {
        const variable = findVariable(key);
        if (!variable) return defaultValue;

        switch (variable.type) {
            case 'number':
                return parseFloat(variable.value);
            case 'boolean':
                return variable.value === 'true';
            case 'json':
                try {
                    return JSON.parse(variable.value);
                } catch {
                    return defaultValue;
                }
            default:
                return variable.value;
        }
    };

    const getVariablesAsRecord = (): Record<string, any> => {
        const record: Record<string, any> = {};
        variables.value.forEach((variable) => {
            record[variable.key] = getVariableValue(variable.key);
        });
        return record;
    };

    return {
        // State
        variables,
        categories,
        loading,
        error,

        // Computed
        variablesByCategory,
        categoryStats,
        systemVariables,
        userVariables,

        // Methods
        loadVariables,
        saveVariable,
        deleteVariable,
        importVariables,
        exportVariables,
        previewText,
        getVariableUsage,
        seedDefaults,

        // Helpers
        findVariable,
        getVariableValue,
        getVariablesAsRecord,
    };
}
