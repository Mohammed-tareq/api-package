<?php

namespace MohammedTareq\ApiConfig\Exceptions;

use MohammedTareq\ApiConfig\Traits\ApiResponserError;
use Illuminate\Http\Request;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;
use Illuminate\Database\PDOException as LaravelPDOException;

// Laravel wrapper
use PDOException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Routing\Exceptions\RouteNotFoundException;
use Illuminate\Auth\Access\AuthorizationException as IlluminateAuthorizationException;
use Illuminate\Support\Facades\Log;

class ApiHandler2
{
    use ApiResponserError;

    /**
     * Handle API exceptions and return consistent JSON response
     */
    public function __invoke(Throwable $e, Request $request)
    {
        if (!($request->is('api/*') || $request->expectsJson() || $request->wantsJson())) {
            return null; // ليس طلب API → نرجع null عشان الـ default handler يشتغل
        }

        $requestId = (string)str()->uuid();

        // تسجيل الخطأ في الـ log (مهم جدًا)
        if ($this->shouldReport($e)) {
            Log::error('API Exception', [
                'request_id' => $requestId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => config('app.debug') ? $e->getTrace() : null,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
        }

        return match (true) {
            // 404 - Resource Not Found
            $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException,
                $e instanceof RouteNotFoundException => $this->apiResponseError(
                'Resource not found',
                404,
                null,
                $requestId,
                'RESOURCE_NOT_FOUND'
            ),

            // 405 - Method Not Allowed
            $e instanceof MethodNotAllowedHttpException => $this->apiResponseError(
                'Method not allowed',
                405,
                null,
                $requestId,
                'METHOD_NOT_ALLOWED'
            ),

            // 401 - Unauthenticated
            $e instanceof AuthenticationException => $this->apiResponseError(
                'Unauthenticated',
                401,
                null,
                $requestId,
                'UNAUTHENTICATED'
            ),

            // 403 - Forbidden / Access Denied
            $e instanceof AuthorizationException,
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => $this->apiResponseError(
                'Forbidden',
                403,
                null,
                $requestId,
                'FORBIDDEN'
            ),

            // 429 - Too Many Requests (Rate Limiting)
            $e instanceof ThrottleRequestsException => $this->apiResponseError(
                'Too many requests',
                429,
                [
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                ],
                $requestId,
                'TOO_MANY_REQUESTS'
            ),

            // 422 - Validation Error
            $e instanceof ValidationException => $this->apiResponseError(
                'Validation failed',
                422,
                $e->errors(),
                $requestId,
                'VALIDATION_ERROR'
            ),

            // Database Query Errors
            $e instanceof QueryException,
                $e instanceof LaravelPDOException,
                $e instanceof PDOException => $this->apiResponseError(
                config('app.debug') ? $e->getMessage() : 'Database error occurred',
                500,
                null,
                $requestId,
                'DATABASE_ERROR'
            ),

            // General HTTP Exceptions (مثل 503, 422, إلخ)
            $e instanceof HttpException => $this->apiresponse(
                $e->getMessage() ?: 'HTTP Exception',
                $e->getStatusCode(),
                null,
                $requestId,
                'HTTP_EXCEPTION'
            ),

            // CSRF Token Mismatch
            $e instanceof TokenMismatchException => $this->apiresponse(
                'CSRF token mismatch',
                419,
                null,
                $requestId,
                'CSRF_TOKEN_MISMATCH'
            ),

            // Default Server Error (500)
            default => $this->apiresponse(
                config('app.debug') ? $e->getMessage() : 'Server error',
                500,
                config('app.debug') ? [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
                $requestId,
                'INTERNAL_SERVER_ERROR'
            ),
        };
    }

    /**
     * Decide whether to report the exception to logs
     */
    protected function shouldReport(Throwable $e): bool
    {
        // يمكنك إضافة استثناءات لا تريد تسجيلها هنا
        return !($e instanceof ValidationException || $e instanceof ThrottleRequestsException);
    }


    
}