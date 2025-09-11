<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class StructuredLoggingService
{
    /**
     * Log structured information with context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $structuredContext = self::buildStructuredContext($context);
        
        Log::channel('daily')->{$level}($message, $structuredContext);
    }

    /**
     * Log AI service interactions
     */
    public static function logAIService(string $action, array $data = [], ?float $duration = null): void
    {
        $context = [
            'service' => 'ai',
            'action' => $action,
            'duration_ms' => $duration ? round($duration * 1000) : null,
            'data' => $data,
        ];

        self::log('info', "AI Service: {$action}", $context);
    }

    /**
     * Log blockchain service interactions
     */
    public static function logBlockchainService(string $action, array $data = [], ?float $duration = null): void
    {
        $context = [
            'service' => 'blockchain',
            'action' => $action,
            'duration_ms' => $duration ? round($duration * 1000) : null,
            'data' => $data,
        ];

        self::log('info', "Blockchain Service: {$action}", $context);
    }

    /**
     * Log authentication events
     */
    public static function logAuthEvent(string $event, array $data = []): void
    {
        $context = [
            'event_type' => 'authentication',
            'event' => $event,
            'data' => $data,
        ];

        self::log('info', "Auth Event: {$event}", $context);
    }

    /**
     * Log profile management events
     */
    public static function logProfileEvent(string $event, array $data = []): void
    {
        $context = [
            'event_type' => 'profile',
            'event' => $event,
            'data' => $data,
        ];

        self::log('info', "Profile Event: {$event}", $context);
    }

    /**
     * Log errors with structured context
     */
    public static function logError(string $message, \Throwable $exception, array $context = []): void
    {
        $errorContext = [
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
        ];

        self::log('error', $message, $errorContext);
    }

    /**
     * Log performance metrics
     */
    public static function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        $context = [
            'event_type' => 'performance',
            'operation' => $operation,
            'duration_ms' => round($duration * 1000),
            'metrics' => $metrics,
        ];

        self::log('info', "Performance: {$operation}", $context);
    }

    /**
     * Build structured context with common fields
     */
    private static function buildStructuredContext(array $context): array
    {
        $baseContext = [
            'timestamp' => now()->toISOString(),
            'request_id' => Request::header('X-Request-ID', uniqid()),
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
        ];

        return array_merge($baseContext, $context);
    }
}
