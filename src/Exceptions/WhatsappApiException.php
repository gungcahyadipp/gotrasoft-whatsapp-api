<?php

namespace Gotrasoft\WhatsappApi\Exceptions;

use RuntimeException;
use Throwable;

class WhatsappApiException extends RuntimeException
{
    protected array $context;
    protected string $endpoint;
    protected string $httpMethod;

    public function __construct(
        string $message = '',
        int $code = 0,
        array $context = [],
        ?Throwable $previous = null,
        string $endpoint = '',
        string $httpMethod = '',
    ) {
        $this->context = $context;
        $this->endpoint = $endpoint;
        $this->httpMethod = $httpMethod;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Raw API error payload (if any).
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * The API endpoint that caused the error.
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * The HTTP method used (GET, POST, PUT, DELETE).
     */
    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    /**
     * Get a human-readable error summary.
     */
    public function getSummary(): string
    {
        $parts = [];

        if ($this->httpMethod && $this->endpoint) {
            $parts[] = "[{$this->httpMethod} {$this->endpoint}]";
        }

        $parts[] = $this->getMessage();

        if ($this->code > 0) {
            $parts[] = "(HTTP {$this->code})";
        }

        return implode(' ', $parts);
    }

    /**
     * Get the error detail from API response context.
     */
    public function getApiErrorMessage(): ?string
    {
        return $this->context['message'] ?? $this->context['error'] ?? null;
    }

    /**
     * Check if this is a specific HTTP status code error.
     */
    public function isStatus(int $status): bool
    {
        return $this->code === $status;
    }

    /**
     * Check if this is an authentication error (401/403).
     */
    public function isAuthError(): bool
    {
        return in_array($this->code, [401, 403]);
    }

    /**
     * Check if this is a not found error (404).
     */
    public function isNotFound(): bool
    {
        return $this->code === 404;
    }

    /**
     * Check if this is a rate limit error (429).
     */
    public function isRateLimited(): bool
    {
        return $this->code === 429;
    }

    /**
     * Check if this is a server error (5xx).
     */
    public function isServerError(): bool
    {
        return $this->code >= 500 && $this->code < 600;
    }

    /**
     * Check if this is a timeout/connection error.
     */
    public function isConnectionError(): bool
    {
        return $this->code === 0 || $this->code === 408;
    }

    /**
     * Convert exception to array for logging/debugging.
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->code,
            'method' => $this->httpMethod,
            'endpoint' => $this->endpoint,
            'context' => $this->context,
        ];
    }
}
