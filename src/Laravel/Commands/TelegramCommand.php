<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Laravel\Models\TelegramBot;
use TexHub\Telegram\Telegram;

/**
 * Base for the Telegram artisan commands. Resolves a bot client from either the
 * `telegram_bots` table (by name or id) or the config-defined bots.
 */
abstract class TelegramCommand extends Command
{
    /**
     * @return array{0: Bot, 1: TelegramBot|null}|null  [client, record]
     */
    protected function resolveBot(?string $identifier): ?array
    {
        // DB-stored bot (by name or numeric id).
        if (Schema::hasTable('telegram_bots')) {
            $record = null;
            if ($identifier !== null) {
                $record = TelegramBot::query()->where('name', $identifier)->first();
                if ($record === null && is_numeric($identifier)) {
                    $record = TelegramBot::query()->find((int) $identifier);
                }
            }

            if ($record !== null) {
                return [$record->client(), $record];
            }
        }

        // Fall back to a config-defined bot (or the default).
        try {
            return [app(Telegram::class)->driver($identifier), null];
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return null;
        }
    }

    /**
     * Build the webhook URL automatically from config (APP_URL + path), or use
     * the explicit TELEGRAM_WEBHOOK_URL override. Appends the bot name when
     * `telegram.webhook.append_bot` is enabled.
     */
    protected function webhookUrl(?string $bot = null): string
    {
        $override = config('telegram.webhook.url');
        if (is_string($override) && trim($override) !== '') {
            return $override;
        }

        $base = rtrim((string) config('app.url'), '/');
        $path = trim((string) config('telegram.webhook.path', 'telegram/webhook'), '/');
        $url = $base . '/' . $path;

        if ($bot !== null && config('telegram.webhook.append_bot')) {
            $url .= '/' . rawurlencode($bot);
        }

        return $url;
    }
}
