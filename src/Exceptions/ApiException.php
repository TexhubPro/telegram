<?php

declare(strict_types=1);

namespace TexHub\Telegram\Exceptions;

/**
 * Thrown when the Telegram Bot API returns `{ "ok": false, ... }`.
 */
class ApiException extends TelegramException
{
    /**
     * @param array<string, mixed> $parameters The `parameters` object (e.g. retry_after, migrate_to_chat_id).
     * @param array<string, mixed> $payload    Full decoded response body.
     */
    public function __construct(
        string $description,
        public readonly int $errorCode,
        public readonly array $parameters = [],
        public readonly array $payload = [],
    ) {
        parent::__construct($description, $errorCode);
    }

    /**
     * @param array<string, mixed> $body
     */
    public static function fromResponse(array $body): self
    {
        return new self(
            description: (string) ($body['description'] ?? 'Telegram API error'),
            errorCode: (int) ($body['error_code'] ?? 0),
            parameters: is_array($body['parameters'] ?? null) ? $body['parameters'] : [],
            payload: $body,
        );
    }

    /** Seconds to wait before retrying (flood control / 429). */
    public function retryAfter(): ?int
    {
        return isset($this->parameters['retry_after']) ? (int) $this->parameters['retry_after'] : null;
    }

    public function isRateLimit(): bool
    {
        return $this->errorCode === 429;
    }

    public function isUnauthorized(): bool
    {
        return $this->errorCode === 401;
    }
}
