/**
 * Utilitários de validação para CNPJ e CPF
 */

/**
 * Remove caracteres não numéricos de uma string
 */
export const onlyNumbers = (value: string): string => {
    return value.replace(/\D/g, '');
};

/**
 * Valida um CNPJ usando o algoritmo oficial da Receita Federal.
 * 
 * @param cnpj - CNPJ a ser validado (com ou sem formatação)
 * @returns true se o CNPJ é válido, false caso contrário
 * 
 * @example
 * validateCNPJ('12.345.678/0001-90') // true (se válido)
 * validateCNPJ('00000000000000') // false
 */
export const validateCNPJ = (cnpj: string): boolean => {
    // Remove caracteres não numéricos
    const cleanCNPJ = onlyNumbers(cnpj);

    // Verifica se tem 14 dígitos
    if (cleanCNPJ.length !== 14) {
        return false;
    }

    // Verifica CNPJs conhecidos como inválidos
    const invalidCNPJs = [
        '00000000000000', '11111111111111', '22222222222222',
        '33333333333333', '44444444444444', '55555555555555',
        '66666666666666', '77777777777777', '88888888888888',
        '99999999999999'
    ];

    if (invalidCNPJs.includes(cleanCNPJ)) {
        return false;
    }

    // Calcula primeiro dígito verificador
    let sum1 = 0;
    const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for (let i = 0; i < 12; i++) {
        sum1 += parseInt(cleanCNPJ[i]) * weights1[i];
    }

    const digit1 = sum1 % 11 < 2 ? 0 : 11 - (sum1 % 11);

    // Calcula segundo dígito verificador
    let sum2 = 0;
    const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for (let i = 0; i < 13; i++) {
        sum2 += parseInt(cleanCNPJ[i]) * weights2[i];
    }

    const digit2 = sum2 % 11 < 2 ? 0 : 11 - (sum2 % 11);

    // Verifica se os dígitos calculados coincidem
    return (
        digit1 === parseInt(cleanCNPJ[12]) &&
        digit2 === parseInt(cleanCNPJ[13])
    );
};

/**
 * Formata um CNPJ no padrão XX.XXX.XXX/XXXX-XX
 * 
 * @param cnpj - CNPJ a ser formatado (apenas números ou formatado parcialmente)
 * @returns CNPJ formatado ou string original se inválido
 * 
 * @example
 * formatCNPJ('12345678000190') // '12.345.678/0001-90'
 * formatCNPJ('123') // '123'
 */
export const formatCNPJ = (value: string): string => {
    const cleanValue = onlyNumbers(value);

    if (cleanValue.length === 0) return '';

    let formatted = cleanValue;

    if (cleanValue.length > 2) {
        formatted = cleanValue.slice(0, 2) + '.' + cleanValue.slice(2);
    }
    if (cleanValue.length > 5) {
        formatted = formatted.slice(0, 6) + '.' + formatted.slice(6);
    }
    if (cleanValue.length > 8) {
        formatted = formatted.slice(0, 10) + '/' + formatted.slice(10);
    }
    if (cleanValue.length > 12) {
        formatted = formatted.slice(0, 15) + '-' + formatted.slice(15);
    }

    return formatted.slice(0, 18); // Limita ao tamanho máximo
};

/**
 * Valida um CPF usando o algoritmo oficial.
 * 
 * @param cpf - CPF a ser validado (com ou sem formatação)
 * @returns true se o CPF é válido, false caso contrário
 * 
 * @example
 * validateCPF('123.456.789-09') // true (se válido)
 * validateCPF('00000000000') // false
 */
export const validateCPF = (cpf: string): boolean => {
    // Remove caracteres não numéricos
    const cleanCPF = onlyNumbers(cpf);

    // Verifica se tem 11 dígitos
    if (cleanCPF.length !== 11) {
        return false;
    }

    // Verifica CPFs conhecidos como inválidos
    const invalidCPFs = [
        '00000000000', '11111111111', '22222222222', '33333333333',
        '44444444444', '55555555555', '66666666666', '77777777777',
        '88888888888', '99999999999'
    ];

    if (invalidCPFs.includes(cleanCPF)) {
        return false;
    }

    // Calcula primeiro dígito verificador
    let sum1 = 0;
    for (let i = 0; i < 9; i++) {
        sum1 += parseInt(cleanCPF[i]) * (10 - i);
    }

    const digit1 = (sum1 * 10) % 11 === 10 ? 0 : (sum1 * 10) % 11;

    // Calcula segundo dígito verificador
    let sum2 = 0;
    for (let i = 0; i < 10; i++) {
        sum2 += parseInt(cleanCPF[i]) * (11 - i);
    }

    const digit2 = (sum2 * 10) % 11 === 10 ? 0 : (sum2 * 10) % 11;

    // Verifica se os dígitos calculados coincidem
    return (
        digit1 === parseInt(cleanCPF[9]) &&
        digit2 === parseInt(cleanCPF[10])
    );
};

/**
 * Formata um CPF no padrão XXX.XXX.XXX-XX
 * 
 * @param cpf - CPF a ser formatado (apenas números ou formatado parcialmente)
 * @returns CPF formatado
 * 
 * @example
 * formatCPF('12345678909') // '123.456.789-09'
 * formatCPF('123') // '123'
 */
export const formatCPF = (value: string): string => {
    const cleanValue = onlyNumbers(value);

    if (cleanValue.length === 0) return '';

    let formatted = cleanValue;

    if (cleanValue.length > 3) {
        formatted = cleanValue.slice(0, 3) + '.' + cleanValue.slice(3);
    }
    if (cleanValue.length > 6) {
        formatted = formatted.slice(0, 7) + '.' + formatted.slice(7);
    }
    if (cleanValue.length > 9) {
        formatted = formatted.slice(0, 11) + '-' + formatted.slice(11);
    }

    return formatted.slice(0, 14); // Limita ao tamanho máximo
};

/**
 * Formata um CEP no padrão XXXXX-XXX
 * 
 * @param cep - CEP a ser formatado
 * @returns CEP formatado
 * 
 * @example
 * formatCEP('12345678') // '12345-678'
 */
export const formatCEP = (value: string): string => {
    const cleanValue = onlyNumbers(value);

    if (cleanValue.length === 0) return '';
    if (cleanValue.length > 5) {
        return cleanValue.slice(0, 5) + '-' + cleanValue.slice(5, 8);
    }

    return cleanValue;
};

/**
 * Valida um CEP (apenas formato, não verifica se existe)
 * 
 * @param cep - CEP a ser validado
 * @returns true se o formato é válido
 */
export const validateCEP = (cep: string): boolean => {
    const cleanCEP = onlyNumbers(cep);
    return cleanCEP.length === 8;
};

/**
 * Formata um telefone no padrão (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
 * 
 * @param phone - Telefone a ser formatado
 * @returns Telefone formatado
 * 
 * @example
 * formatPhone('11999998888') // '(11) 99999-8888'
 * formatPhone('1133334444') // '(11) 3333-4444'
 */
export const formatPhone = (value: string): string => {
    const cleanValue = onlyNumbers(value);

    if (cleanValue.length === 0) return '';

    if (cleanValue.length <= 2) {
        return `(${cleanValue}`;
    }

    if (cleanValue.length <= 6) {
        return `(${cleanValue.slice(0, 2)}) ${cleanValue.slice(2)}`;
    }

    if (cleanValue.length <= 10) {
        return `(${cleanValue.slice(0, 2)}) ${cleanValue.slice(2, 6)}-${cleanValue.slice(6)}`;
    }

    // Celular com 9 dígitos
    return `(${cleanValue.slice(0, 2)}) ${cleanValue.slice(2, 7)}-${cleanValue.slice(7, 11)}`;
};

/**
 * Valida um email
 * 
 * @param email - Email a ser validado
 * @returns true se o email é válido
 */
export const validateEmail = (email: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

/**
 * Valida uma URL
 * 
 * @param url - URL a ser validada
 * @returns true se a URL é válida
 */
export const validateURL = (url: string): boolean => {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
};

/**
 * Formata uma URL adicionando https:// se necessário
 * 
 * @param url - URL a ser formatada
 * @returns URL formatada
 */
export const formatURL = (url: string): string => {
    if (!url) return '';

    const trimmed = url.trim();
    if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
        return trimmed;
    }

    return `https://${trimmed}`;
};

/**
 * Mascara de entrada para CNPJ (para uso em inputs com onChange)
 * 
 * @param event - Evento de change do input
 */
export const maskCNPJ = (event: React.ChangeEvent<HTMLInputElement>): void => {
    const value = event.target.value;
    event.target.value = formatCNPJ(value);
};

/**
 * Mascara de entrada para CPF (para uso em inputs com onChange)
 * 
 * @param event - Evento de change do input
 */
export const maskCPF = (event: React.ChangeEvent<HTMLInputElement>): void => {
    const value = event.target.value;
    event.target.value = formatCPF(value);
};

/**
 * Mascara de entrada para CEP (para uso em inputs com onChange)
 * 
 * @param event - Evento de change do input
 */
export const maskCEP = (event: React.ChangeEvent<HTMLInputElement>): void => {
    const value = event.target.value;
    event.target.value = formatCEP(value);
};

/**
 * Mascara de entrada para telefone (para uso em inputs com onChange)
 * 
 * @param event - Evento de change do input
 */
export const maskPhone = (event: React.ChangeEvent<HTMLInputElement>): void => {
    const value = event.target.value;
    event.target.value = formatPhone(value);
};
