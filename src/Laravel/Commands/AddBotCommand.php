<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Commands;

use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Laravel\Models\TelegramBot;

/**
 * Interactively add a Telegram bot: validate the token, optionally generate a
 * webhook secret, optionally register the webhook, and save it to the database.
 */
final class AddBotCommand extends TelegramCommand
{
    protected $signature = 'telegram:bot:add {token? : Bot token from @BotFather}';

    protected $description = 'Add a Telegram bot (token, optional secret & webhook) and store it';

    public function handle(): int
    {
        $token = (string) ($this->argument('token') ?: $this->ask('Enter the bot token from @BotFather'));
        if (trim($token) === '') {
            $this->error('A token is required.');

            return self::FAILURE;
        }

        // Validate the token by calling getMe.
        $bot = new Bot(new Config($token));
        try {
            $me = $bot->getMe();
        } catch (\Throwable $e) {
            $this->error('Invalid token: ' . $e->getMessage());

            return self::FAILURE;
        }

        $username = (string) $me->get('username');
        $this->info("✅ Connected to @{$username} ({$me->get('first_name')})");

        $name = (string) $this->ask('A short name for this bot', $username);

        // Webhook secret.
        $secret = null;
        if ($this->confirm('Set a webhook secret token? (recommended)', true)) {
            $secret = bin2hex(random_bytes(16));
            $this->line("  Generated secret: <info>{$secret}</info>");
        }

        // Webhook registration.
        if ($this->confirm('Register a webhook now?', false)) {
            $default = rtrim((string) config('app.url'), '/') . '/telegram/webhook';
            $url = (string) $this->ask('Webhook URL', $default);

            try {
                $bot->setWebhook($url, array_filter(['secret_token' => $secret]));
                $this->info("🔔 Webhook set to {$url}");
            } catch (\Throwable $e) {
                $this->error('Failed to set webhook: ' . $e->getMessage());
            }
        }

        // Persist.
        try {
            $record = TelegramBot::updateOrCreate(
                ['token' => $token],
                ['name' => $name, 'username' => $username, 'webhook_secret' => $secret, 'is_active' => true],
            );
            $this->info("💾 Saved bot #{$record->id} (@{$username}).");
        } catch (\Throwable $e) {
            $this->warn('Bot validated but not saved to DB. Publish & run the migration first:');
            $this->warn('  php artisan vendor:publish --tag=telegram-migrations && php artisan migrate');
            $this->warn('  (' . $e->getMessage() . ')');
        }

        return self::SUCCESS;
    }
}
