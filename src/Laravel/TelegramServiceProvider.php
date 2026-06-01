<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\Telegram\Telegram as TelegramManager;

class TelegramServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/telegram.php', 'telegram');

        $this->app->singleton(TelegramManager::class, function ($app): TelegramManager {
            return TelegramManager::fromArray((array) $app['config']->get('telegram', []));
        });

        $this->app->alias(TelegramManager::class, 'telegram');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/telegram.php' => $this->app->configPath('telegram.php'),
            ], 'telegram-config');

            $this->publishes([
                __DIR__ . '/../../database/migrations/create_telegram_bots_table.php.stub'
                    => $this->app->databasePath('migrations/' . date('Y_m_d_His', 0) . '_create_telegram_bots_table.php'),
                __DIR__ . '/../../database/migrations/create_telegram_chats_table.php.stub'
                    => $this->app->databasePath('migrations/' . date('Y_m_d_His', 1) . '_create_telegram_chats_table.php'),
            ], 'telegram-migrations');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [TelegramManager::class, 'telegram'];
    }
}
