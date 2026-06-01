<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

/**
 * Remove the webhook for a bot.
 */
final class UnsetWebhookCommand extends TelegramCommand
{
    protected $signature = 'telegram:webhook:unset {bot? : Bot name or id} {--drop-pending : Drop pending updates}';

    protected $description = 'Delete the Telegram webhook for a bot';

    public function handle(): int
    {
        $resolved = $this->resolveBot($this->argument('bot'));
        if ($resolved === null) {
            return self::FAILURE;
        }

        [$bot] = $resolved;

        try {
            $bot->unsetWebhook((bool) $this->option('drop-pending'));
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('🧹 Webhook removed.');

        return self::SUCCESS;
    }
}
