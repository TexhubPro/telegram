<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Update;

/**
 * Eloquent model for every chat/user that interacts with your bots (optional).
 *
 * Publish & run the migration:
 *   php artisan vendor:publish --tag=telegram-migrations
 *
 * Auto-save a chat from an incoming update:
 *   TelegramChat::rememberFromUpdate($update, $botId);
 *
 * @property string      $chat_id
 * @property string|null $type
 * @property string|null $name
 * @property string|null $username
 * @property string|null $avatar
 * @property bool         $is_active
 */
class TelegramChat extends Model
{
    protected $table = 'telegram_chats';

    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'is_bot' => 'boolean',
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'last_interaction_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    /**
     * Create or update a chat record from an incoming update.
     */
    public static function rememberFromUpdate(Update $update, ?int $telegramBotId = null): ?self
    {
        $chatId = $update->chatId();
        if ($chatId === null) {
            return null;
        }

        $chat = $update->message()['chat'] ?? [];
        $from = $update->from() ?? [];

        $firstName = $chat['first_name'] ?? $from['first_name'] ?? null;
        $lastName = $chat['last_name'] ?? $from['last_name'] ?? null;
        $title = $chat['title'] ?? null;
        $name = $title ?? trim((string) $firstName . ' ' . (string) $lastName) ?: ($chat['username'] ?? $from['username'] ?? null);

        $attributes = [
            'type' => $chat['type'] ?? null,
            'title' => $title,
            'username' => $chat['username'] ?? $from['username'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name !== '' ? $name : null,
            'language_code' => $from['language_code'] ?? null,
            'is_bot' => (bool) ($from['is_bot'] ?? false),
            'is_active' => true,
            'last_interaction_at' => now(),
        ];

        return static::query()->updateOrCreate(
            ['telegram_bot_id' => $telegramBotId, 'chat_id' => (string) $chatId],
            $attributes,
        );
    }

    /**
     * Fetch & store the chat's avatar (profile photo) file id using the bot.
     */
    public function refreshAvatar(Bot $bot): self
    {
        $photos = $bot->call('getUserProfilePhotos', ['user_id' => $this->chat_id, 'limit' => 1]);
        $fileId = $photos->get('photos.0.0.file_id');

        if ($fileId !== null) {
            $this->avatar = (string) $fileId;
            $this->save();
        }

        return $this;
    }

    /**
     * Resolve a downloadable URL for the stored avatar file id.
     */
    public function avatarUrl(Bot $bot): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        $path = $bot->getFile($this->avatar)->get('file_path');

        return $path === null ? null : $bot->config()->fileUrl((string) $path);
    }
}
