<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

/**
 * Show webhook info for a bot.
 */
final class WebhookInfoCommand extends TelegramCommand
{
    protected $signature = 'telegram:webhook:info {bot? : Bot name or id}';

    protected $description = 'Show the current Telegram webhook info for a bot';

    public function handle(): int
    {
        $resolved = $this->resolveBot($this->argument('bot'));
        if ($resolved === null) {
            return self::FAILURE;
        }

        [$bot] = $resolved;

        try {
            $info = $bot->getWebhookInfo()->toArray();
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $rows = [];
        foreach ($info as $key => $value) {
            $rows[] = [$key, is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)];
        }
        $this->table(['Field', 'Value'], $rows);

        return self::SUCCESS;
    }
}
