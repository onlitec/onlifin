<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * Exibir documentação da API
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Onlifin API Documentation',
            'version' => '1.0.0',
            'description' => 'API completa para gestão financeira pessoal - Plataforma Onlifin',
            'base_url' => config('app.url') . '/api',
            'authentication' => 'Bearer Token (Laravel Sanctum)',
            'endpoints' => [
                'auth' => [
                    'login' => 'POST /api/auth/login',
                    'register' => 'POST /api/auth/register',
                    'logout' => 'POST /api/auth/logout',
                    'logout_all' => 'POST /api/auth/logout-all',
                    'refresh' => 'POST /api/auth/refresh',
                    'me' => 'GET /api/auth/me',
                    'verify_token' => 'GET /api/auth/verify-token',
                    'tokens' => 'GET /api/auth/tokens',
                    'revoke_token' => 'DELETE /api/auth/tokens/{tokenId}'
                ],
                'transactions' => [
                    'list' => 'GET /api/transactions',
                    'create' => 'POST /api/transactions',
                    'summary' => 'GET /api/transactions/summary',
                    'show' => 'GET /api/transactions/{id}',
                    'update' => 'PUT /api/transactions/{id}',
                    'delete' => 'DELETE /api/transactions/{id}'
                ],
                'accounts' => [
                    'list' => 'GET /api/accounts',
                    'create' => 'POST /api/accounts',
                    'summary' => 'GET /api/accounts/summary',
                    'show' => 'GET /api/accounts/{id}',
                    'update' => 'PUT /api/accounts/{id}',
                    'delete' => 'DELETE /api/accounts/{id}'
                ],
                'categories' => [
                    'list' => 'GET /api/categories',
                    'create' => 'POST /api/categories',
                    'stats' => 'GET /api/categories/stats',
                    'show' => 'GET /api/categories/{id}',
                    'update' => 'PUT /api/categories/{id}',
                    'delete' => 'DELETE /api/categories/{id}'
                ],
                'reports' => [
                    'dashboard' => 'GET /api/reports/dashboard',
                    'cash_flow' => 'GET /api/reports/cash-flow',
                    'by_category' => 'GET /api/reports/by-category',
                    'by_account' => 'GET /api/reports/by-account'
                ],
                'settings' => [
                    'get' => 'GET /api/settings',
                    'update_profile' => 'PUT /api/settings/profile',
                    'update_photo' => 'POST /api/settings/profile/photo',
                    'update_password' => 'PUT /api/settings/password',
                    'update_notifications' => 'PUT /api/settings/notifications',
                    'update_two_factor' => 'PUT /api/settings/two-factor',
                    'delete_account' => 'DELETE /api/settings/account',
                    'export_data' => 'GET /api/settings/export'
                ],
                'ai' => [
                    'chat' => 'POST /api/ai/chat',
                    'analysis' => 'POST /api/ai/analysis',
                    'categorization' => 'POST /api/ai/categorization',
                    'insights' => 'GET /api/ai/insights'
                ]
            ],
            'rate_limits' => [
                'authenticated' => '60 requests per minute per user',
                'unauthenticated' => '10 requests per minute per IP'
            ],
            'response_format' => [
                'success' => [
                    'success' => true,
                    'data' => '...',
                    'timestamp' => '2024-01-01T00:00:00.000000Z'
                ],
                'error' => [
                    'success' => false,
                    'message' => 'Error message',
                    'errors' => '...',
                    'timestamp' => '2024-01-01T00:00:00.000000Z'
                ]
            ]
        ]);
    }

    /**
     * Retornar especificação OpenAPI simplificada
     */
    public function openapi(): JsonResponse
    {
        $spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Onlifin API',
                'description' => 'API completa para gestão financeira pessoal - Plataforma Onlifin',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Onlifin Support',
                    'email' => 'support@onlifin.com',
                ],
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => 'Servidor Principal',
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
        ];

        return response()->json($spec);
    }
}
