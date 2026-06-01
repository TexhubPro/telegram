<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

use Illuminate\Support\Facades\Schema;
use TexHub\Telegram\Laravel\Models\TelegramBot;
use TexHub\Telegram\Telegram;

/**
 * List configured & stored bots.
 */
final class ListBotsCommand extends TelegramCommand
{
    protected $signature = 'telegram:bots';

    protected $description = 'List Telegram bots (from the database and config)';

    public function handle(): int
    {
        $rows = [];

        if (Schema::hasTable('telegram_bots')) {
            foreach (TelegramBot::all() as $bot) {
                $rows[] = [
                    'db #' . $bot->id,
                    $bot->name ?? '—',
                    $bot->username ? '@' . $bot->username : '—',
                    $bot->webhook_secret ? 'yes' : 'no',
                    $bot->is_active ? 'active' : 'inactive',
                ];
            }
        }

        foreach (app(Telegram::class)->names() as $name) {
            $rows[] = ['config', $name, '—', '—', '—'];
        }

        if ($rows === []) {
            $this->warn('No bots found. Add one with: php artisan telegram:bot:add');

            return self::SUCCESS;
        }

        $this->table(['Source', 'Name', 'Username', 'Secret', 'Status'], $rows);

        return self::SUCCESS;
    }
}
