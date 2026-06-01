<?php

declare(strict_types=1);

namespace TexHub\Telegram\Keyboard;

/**
 * Factory for inline and reply keyboard buttons.
 */
final class Button
{
    // ---- Inline buttons ---------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public static function callback(string $text, string $data): array
    {
        return ['text' => $text, 'callback_data' => $data];
    }

    /**
     * @return array<string, mixed>
     */
    public static function url(string $text, string $url): array
    {
        return ['text' => $text, 'url' => $url];
    }

    /**
     * @return array<string, mixed>
     */
    public static function webApp(string $text, string $url): array
    {
        return ['text' => $text, 'web_app' => ['url' => $url]];
    }

    /**
     * @return array<string, mixed>
     */
    public static function switchInline(string $text, string $query = ''): array
    {
        return ['text' => $text, 'switch_inline_query' => $query];
    }

    /**
     * @return array<string, mixed>
     */
    public static function switchInlineCurrent(string $text, string $query = ''): array
    {
        return ['text' => $text, 'switch_inline_query_current_chat' => $query];
    }

    /**
     * @return array<string, mixed>
     */
    public static function pay(string $text): array
    {
        return ['text' => $text, 'pay' => true];
    }

    /**
     * A button that copies text to the clipboard when tapped (Bot API 7.11+).
     *
     * @return array<string, mixed>
     */
    public static function copyText(string $text, string $copy): array
    {
        return ['text' => $text, 'copy_text' => ['text' => $copy]];
    }

    /**
     * A Login URL button (Telegram Login / seamless authorization).
     *
     * @param array<string, mixed> $options forward_text, bot_username, request_write_access
     *
     * @return array<string, mixed>
     */
    public static function loginUrl(string $text, string $url, array $options = []): array
    {
        return ['text' => $text, 'login_url' => ['url' => $url] + $options];
    }

    /**
     * A button that launches a Telegram Game.
     *
     * @return array<string, mixed>
     */
    public static function callbackGame(string $text): array
    {
        return ['text' => $text, 'callback_game' => new \stdClass()];
    }

    // ---- Reply buttons ----------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public static function text(string $text): array
    {
        return ['text' => $text];
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestContact(string $text): array
    {
        return ['text' => $text, 'request_contact' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestLocation(string $text): array
    {
        return ['text' => $text, 'request_location' => true];
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestWebApp(string $text, string $url): array
    {
        return ['text' => $text, 'web_app' => ['url' => $url]];
    }

    /**
     * Ask the user to pick one or more users.
     *
     * @param array<string, mixed> $options max_quantity, user_is_bot, user_is_premium, request_name/username/photo
     *
     * @return array<string, mixed>
     */
    public static function requestUsers(string $text, int $requestId, array $options = []): array
    {
        return ['text' => $text, 'request_users' => ['request_id' => $requestId] + $options];
    }

    /**
     * Ask the user to pick a chat/channel.
     *
     * @param array<string, mixed> $options chat_is_forum, chat_has_username, bot_is_member, …
     *
     * @return array<string, mixed>
     */
    public static function requestChat(string $text, int $requestId, bool $chatIsChannel = false, array $options = []): array
    {
        return ['text' => $text, 'request_chat' => ['request_id' => $requestId, 'chat_is_channel' => $chatIsChannel] + $options];
    }

    /**
     * Ask the user to create a poll.
     *
     * @return array<string, mixed>
     */
    public static function requestPoll(string $text, ?string $type = null): array
    {
        return ['text' => $text, 'request_poll' => $type === null ? new \stdClass() : ['type' => $type]];
    }
}
