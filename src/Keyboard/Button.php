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
}
