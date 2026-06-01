<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;

/**
 * Eloquent model for DB-driven multi-tenant bots (optional).
 *
 * Publish & run the migration:
 *   php artisan vendor:publish --tag=telegram-migrations
 *
 * @property string      $token
 * @property string|null $name
 * @property string|null $username
 * @property string|null $webhook_secret
 * @property array|null  $settings
 * @property bool         $is_active
 */
class TelegramBot extends Model
{
    protected $table = 'telegram_bots';

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['token', 'webhook_secret'];

    /**
     * Build a ready-to-use Bot client from this record.
     */
    public function client(): Bot
    {
        return new Bot(new Config(
            token: (string) $this->token,
            webhookSecret: $this->webhook_secret,
            defaultParseMode: $this->settings['parse_mode'] ?? null,
        ));
    }

    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * All chats/users that interacted with this bot.
     */
    public function chats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TelegramChat::class, 'telegram_bot_id');
    }
}
