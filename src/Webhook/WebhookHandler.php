<?php

declare(strict_types=1);

namespace TexHub\Telegram\Webhook;

use TexHub\Telegram\Config;
use TexHub\Telegram\Exceptions\InvalidWebhookException;
use TexHub\Telegram\Exceptions\TelegramException;
use TexHub\Telegram\Update;

/**
 * Verifies and parses incoming Telegram webhook requests.
 *
 * Security: Telegram sends the `secret_token` you set with setWebhook in the
 * `X-Telegram-Bot-Api-Secret-Token` header. Always verify it.
 */
final class WebhookHandler
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Verify the secret-token header against the configured webhook secret.
     */
    public function verify(?string $secretHeader): bool
    {
        if ($this->config->webhookSecret === null) {
            return true; // no secret configured — nothing to verify
        }

        return $secretHeader !== null && hash_equals($this->config->webhookSecret, $secretHeader);
    }

    /**
     * @throws InvalidWebhookException
     */
    public function assertValid(?string $secretHeader): void
    {
        if (! $this->verify($secretHeader)) {
            throw new InvalidWebhookException('Telegram webhook secret token verification failed.');
        }
    }

    /**
     * Parse a webhook body into an {@see Update}.
     *
     * @param string|array<string, mixed> $body
     *
     * @throws TelegramException On invalid JSON.
     */
    public function parse(string|array $body): Update
    {
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (! is_array($decoded)) {
                throw new TelegramException('Webhook body is not valid JSON.');
            }
            $body = $decoded;
        }

        return new Update($body);
    }
}
