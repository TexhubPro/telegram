<?php

declare(strict_types=1);

namespace TexHub\Telegram;

use TexHub\Telegram\Exceptions\ConfigurationException;

/**
 * Immutable configuration for a single Telegram bot.
 */
final class Config
{
    public const DEFAULT_API_URL = 'https://api.telegram.org';

    /**
     * @param string      $token         Bot token from @BotFather.
     * @param string|null $webhookSecret Secret token validated on incoming webhooks.
     * @param string|null $defaultParseMode Default parse_mode for text messages (e.g. "HTML").
     * @param string      $apiUrl        Bot API base URL (override for local Bot API server).
     * @param int         $timeout       HTTP timeout in seconds.
     */
    public function __construct(
        public readonly string $token,
        public readonly ?string $webhookSecret = null,
        public readonly ?string $defaultParseMode = null,
        public readonly string $apiUrl = self::DEFAULT_API_URL,
        public readonly int $timeout = 30,
    ) {
        if (trim($this->token) === '') {
            throw new ConfigurationException('Telegram bot token must not be empty.');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('Telegram timeout must be a positive number of seconds.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            token: (string) ($config['token'] ?? ''),
            webhookSecret: self::nullableString($config['webhook_secret'] ?? null),
            defaultParseMode: self::nullableString($config['parse_mode'] ?? $config['default_parse_mode'] ?? null),
            apiUrl: (string) ($config['api_url'] ?? self::DEFAULT_API_URL),
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    /**
     * Build the URL for an API method.
     */
    public function methodUrl(string $method): string
    {
        return rtrim($this->apiUrl, '/') . '/bot' . $this->token . '/' . $method;
    }

    /**
     * Build the URL for downloading a file by its file path.
     */
    public function fileUrl(string $filePath): string
    {
        return rtrim($this->apiUrl, '/') . '/file/bot' . $this->token . '/' . ltrim($filePath, '/');
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
