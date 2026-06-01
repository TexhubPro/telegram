<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

/**
 * Register the webhook for a bot.
 */
final class SetWebhookCommand extends TelegramCommand
{
    protected $signature = 'telegram:webhook:set {bot? : Bot name or id} {url? : Webhook URL (auto-built from APP_URL when omitted)} {--secret= : Secret token (auto-generated if omitted and none stored)}';

    protected $description = 'Set the Telegram webhook for a bot (URL auto-generated from config)';

    public function handle(): int
    {
        $resolved = $this->resolveBot($this->argument('bot'));
        if ($resolved === null) {
            return self::FAILURE;
        }

        [$bot, $record] = $resolved;

        // Auto-build the URL from config (APP_URL + telegram.webhook.path) unless one is passed.
        $url = (string) ($this->argument('url') ?: $this->webhookUrl($this->argument('bot')));

        // Auto-generate a secret if none is provided/stored.
        $secret = $this->option('secret')
            ?: ($record?->webhook_secret)
            ?: $bot->config()->webhookSecret
            ?: bin2hex(random_bytes(16));

        try {
            $bot->setWebhook($url, array_filter(['secret_token' => $secret]));
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($record !== null && $secret && $record->webhook_secret !== $secret) {
            $record->update(['webhook_secret' => $secret]);
        }

        $this->info("🔔 Webhook set to {$url}");
        if ($secret) {
            $this->line("  secret: <info>{$secret}</info>");
        }

        return self::SUCCESS;
    }
}
