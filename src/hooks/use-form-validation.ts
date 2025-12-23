import * as React from 'react';
import {
    sanitizeString,
    sanitizeDescription,
    sanitizeMoneyValue,
    isValidEmail,
    isValidUUID,
    validatePassword,
    isValidUsername
} from '@/utils/security';

/**
 * Hook para validação e sanitização de formulários
 */
export function useFormValidation() {
    const [errors, setErrors] = React.useState<Record<string, string>>({});

    /**
     * Limpa todos os erros
     */
    const clearErrors = React.useCallback(() => {
        setErrors({});
    }, []);

    /**
     * Define um erro específico
     */
    const setError = React.useCallback((field: string, message: string) => {
        setErrors(prev => ({ ...prev, [field]: message }));
    }, []);

    /**
     * Remove um erro específico
     */
    const clearError = React.useCallback((field: string) => {
        setErrors(prev => {
            const newErrors = { ...prev };
            delete newErrors[field];
            return newErrors;
        });
    }, []);

    /**
     * Valida campo obrigatório
     */
    const validateRequired = React.useCallback((value: any, fieldName: string): boolean => {
        if (value === null || value === undefined || value === '') {
            setError(fieldName, 'Campo obrigatório');
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida email
     */
    const validateEmail = React.useCallback((value: string, fieldName: string = 'email'): boolean => {
        if (!isValidEmail(value)) {
            setError(fieldName, 'Email inválido');
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida senha
     */
    const validatePasswordField = React.useCallback((value: string, fieldName: string = 'password'): boolean => {
        const result = validatePassword(value);
        if (!result.valid) {
            setError(fieldName, result.message);
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida username
     */
    const validateUsernameField = React.useCallback((value: string, fieldName: string = 'username'): boolean => {
        if (!isValidUsername(value)) {
            setError(fieldName, 'Username deve ter 3-50 caracteres (letras, números e _)');
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida UUID
     */
    const validateUUIDField = React.useCallback((value: string, fieldName: string): boolean => {
        if (!isValidUUID(value)) {
            setError(fieldName, 'ID inválido');
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida valor monetário
     */
    const validateMoneyField = React.useCallback((value: string | number, fieldName: string = 'amount'): boolean => {
        const sanitized = sanitizeMoneyValue(value);
        if (sanitized === 0 && value !== 0 && value !== '0') {
            setError(fieldName, 'Valor inválido');
            return false;
        }
        if (sanitized < 0) {
            setError(fieldName, 'Valor não pode ser negativo');
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Valida tamanho máximo de texto
     */
    const validateMaxLength = React.useCallback((
        value: string,
        maxLength: number,
        fieldName: string
    ): boolean => {
        if (value && value.length > maxLength) {
            setError(fieldName, `Máximo de ${maxLength} caracteres`);
            return false;
        }
        clearError(fieldName);
        return true;
    }, [setError, clearError]);

    /**
     * Sanitiza e retorna valor de texto
     */
    const sanitizeText = React.useCallback((value: string): string => {
        return sanitizeString(value);
    }, []);

    /**
     * Sanitiza e retorna descrição
     */
    const sanitizeDesc = React.useCallback((value: string, maxLength: number = 500): string => {
        return sanitizeDescription(value, maxLength);
    }, []);

    /**
     * Sanitiza e retorna valor monetário
     */
    const sanitizeMoney = React.useCallback((value: string | number): number => {
        return sanitizeMoneyValue(value);
    }, []);

    return {
        errors,
        hasErrors: Object.keys(errors).length > 0,
        setError,
        setErrors,
        clearError,
        clearErrors,
        validateRequired,
        validateEmail,
        validatePassword: validatePasswordField,
        validateUsername: validateUsernameField,
        validateUUID: validateUUIDField,
        validateMoney: validateMoneyField,
        validateMaxLength,
        sanitizeText,
        sanitizeDescription: sanitizeDesc,
        sanitizeMoney
    };
}

export default useFormValidation;
