// ===========================================
// Onlifin - Utilitários de Segurança
// ===========================================
// Funções para sanitização e validação de inputs

/**
 * Sanitiza string removendo caracteres perigosos para XSS
 */
export function sanitizeString(input: string): string {
    if (!input || typeof input !== 'string') return '';

    return input
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/\//g, '&#x2F;');
}

/**
 * Remove tags HTML de uma string
 */
export function stripHtml(input: string): string {
    if (!input || typeof input !== 'string') return '';
    return input.replace(/<[^>]*>/g, '');
}

/**
 * Valida formato de email
 */
export function isValidEmail(email: string): boolean {
    if (!email || typeof email !== 'string') return false;
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return emailRegex.test(email);
}

/**
 * Valida força de senha
 * Retorna: { valid: boolean, message: string }
 */
export function validatePassword(password: string): { valid: boolean; message: string } {
    if (!password || typeof password !== 'string') {
        return { valid: false, message: 'Senha é obrigatória' };
    }

    if (password.length < 8) {
        return { valid: false, message: 'Senha deve ter no mínimo 8 caracteres' };
    }

    if (password.length > 128) {
        return { valid: false, message: 'Senha muito longa (máximo 128 caracteres)' };
    }

    // Verifica se tem pelo menos uma letra e um número
    if (!/[a-zA-Z]/.test(password) || !/[0-9]/.test(password)) {
        return { valid: false, message: 'Senha deve conter letras e números' };
    }

    return { valid: true, message: 'Senha válida' };
}

/**
 * Valida username (apenas letras, números e underscore)
 */
export function isValidUsername(username: string): boolean {
    if (!username || typeof username !== 'string') return false;
    if (username.length < 3 || username.length > 50) return false;
    return /^[a-zA-Z0-9_]+$/.test(username);
}

/**
 * Sanitiza descrição de transação
 */
export function sanitizeDescription(input: string, maxLength: number = 500): string {
    if (!input || typeof input !== 'string') return '';

    // Remove caracteres de controle e limita tamanho
    return stripHtml(input)
        .replace(/[\x00-\x1F\x7F]/g, '')
        .trim()
        .substring(0, maxLength);
}

/**
 * Valida e sanitiza valor monetário
 */
export function sanitizeMoneyValue(value: string | number): number {
    if (typeof value === 'number') {
        return Math.round(value * 100) / 100;
    }

    if (typeof value !== 'string') return 0;

    // Remove caracteres não numéricos exceto ponto e vírgula
    const cleaned = value.replace(/[^\d.,\-]/g, '').replace(',', '.');
    const parsed = parseFloat(cleaned);

    if (isNaN(parsed)) return 0;

    return Math.round(parsed * 100) / 100;
}

/**
 * Valida UUID v4
 */
export function isValidUUID(uuid: string): boolean {
    if (!uuid || typeof uuid !== 'string') return false;
    const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
    return uuidRegex.test(uuid);
}

/**
 * Limita taxa de tentativas (rate limiting no cliente)
 * Retorna true se deve bloquear a ação
 */
const rateLimitMap = new Map<string, { count: number; resetAt: number }>();

export function checkRateLimit(
    key: string,
    maxAttempts: number = 5,
    windowMs: number = 60000
): { blocked: boolean; remainingAttempts: number } {
    const now = Date.now();
    const record = rateLimitMap.get(key);

    if (!record || now > record.resetAt) {
        rateLimitMap.set(key, { count: 1, resetAt: now + windowMs });
        return { blocked: false, remainingAttempts: maxAttempts - 1 };
    }

    record.count++;

    if (record.count > maxAttempts) {
        return { blocked: true, remainingAttempts: 0 };
    }

    return { blocked: false, remainingAttempts: maxAttempts - record.count };
}

/**
 * Reset rate limit para uma chave
 */
export function resetRateLimit(key: string): void {
    rateLimitMap.delete(key);
}

/**
 * Gera nonce para CSP (Content Security Policy)
 */
export function generateNonce(): string {
    const array = new Uint8Array(16);
    crypto.getRandomValues(array);
    return btoa(String.fromCharCode(...array));
}

/**
 * Verifica se a sessão é válida e não expirou
 */
export function isSessionValid(expiresAt?: number): boolean {
    if (!expiresAt) return false;
    return Date.now() / 1000 < expiresAt;
}

/**
 * Mascara dados sensíveis para logs
 */
export function maskSensitiveData(data: string, visibleChars: number = 4): string {
    if (!data || data.length <= visibleChars * 2) {
        return '*'.repeat(data?.length || 8);
    }

    const start = data.substring(0, visibleChars);
    const end = data.substring(data.length - visibleChars);
    const middle = '*'.repeat(Math.min(data.length - visibleChars * 2, 8));

    return `${start}${middle}${end}`;
}

export default {
    sanitizeString,
    stripHtml,
    isValidEmail,
    validatePassword,
    isValidUsername,
    sanitizeDescription,
    sanitizeMoneyValue,
    isValidUUID,
    checkRateLimit,
    resetRateLimit,
    generateNonce,
    isSessionValid,
    maskSensitiveData
};
