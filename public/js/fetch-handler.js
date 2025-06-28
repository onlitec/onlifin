/**
 * Onlifin - Manipulador de requisições fetch
 * 
 * Este módulo fornece funções para lidar com requisições fetch e tratar erros de JSON
 * de forma mais robusta, evitando o erro "Unexpected token '<', <!-- Stub... is not valid JSON"
 */

// Função para verificar se uma resposta é JSON válido
function isJsonResponse(response) {
    const contentType = response.headers.get('content-type');
    return contentType && contentType.indexOf('application/json') !== -1;
}

// Função para fazer requisição fetch com tratamento de erros
function safeFetch(url, options = {}) {
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            
            // Verificar o tipo de conteúdo da resposta
            if (isJsonResponse(response)) {
                return response.json().catch(e => {
                    console.error('Erro ao parsear JSON:', e);
                    throw new Error('Erro ao processar resposta do servidor');
                });
            } else {
                console.warn('Resposta não é JSON:', url);
                // Se não for JSON, retornar o texto da resposta
                return response.text().then(text => {
                    // Tentar parsear como JSON mesmo assim (pode ser que o Content-Type esteja errado)
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // Se não for JSON, retornar um objeto com o texto
                        return {
                            success: false,
                            message: 'Resposta não é JSON',
                            htmlResponse: text
                        };
                    }
                });
            }
        });
}

// Função para fazer requisição fetch com redirecionamento automático
function fetchWithRedirect(url, options = {}, redirectUrl = null) {
    return safeFetch(url, options)
        .then(data => {
            if (data.success) {
                // Se a requisição foi bem-sucedida e temos uma URL de redirecionamento
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
                return data;
            } else {
                throw new Error(data.message || 'Erro desconhecido');
            }
        })
        .catch(error => {
            console.error('Erro no fetch:', error);
            
            // Se temos uma URL de redirecionamento, redirecionar mesmo com erro
            if (redirectUrl) {
                console.log('Redirecionando mesmo com erro para:', redirectUrl);
                window.location.href = redirectUrl;
                // Lançar um erro especial para interromper a cadeia de promises
                throw new Error('Redirecionando...');
            } else {
                throw error;
            }
        });
}

// Exportar funções para uso global
window.safeFetch = safeFetch;
window.fetchWithRedirect = fetchWithRedirect;
window.isJsonResponse = isJsonResponse;
