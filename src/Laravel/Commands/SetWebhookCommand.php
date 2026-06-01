<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

/**
 * Register the webhook for a bot.
 */
final class SetWebhookCommand extends TelegramCommand
{
    protected $signature = 'telegram:webhook:set {bot? : Bot name or id} {url? : Webhook URL} {--secret= : Secret token (auto-generated if omitted and none stored)}';

    protected $description = 'Set the Telegram webhook for a bot';

    public function handle(): int
    {
        $resolved = $this->resolveBot($this->argument('bot'));
        if ($resolved === null) {
            return self::FAILURE;
        }

        [$bot, $record] = $resolved;

        $url = (string) ($this->argument('url')
            ?: $this->ask('Webhook URL', rtrim((string) config('app.url'), '/') . '/telegram/webhook'));

        $secret = $this->option('secret') ?: ($record?->webhook_secret) ?: $bot->config()->webhookSecret;

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
