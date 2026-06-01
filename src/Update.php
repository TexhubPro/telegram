<?php

declare(strict_types=1);

namespace TexHub\Telegram;

/**
 * A parsed Telegram Update with convenient accessors for the common cases,
 * including Telegram Business updates.
 *
 * @see https://core.telegram.org/bots/api#update
 */
final class Update
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly array $data = [],
    ) {
    }

    public function id(): ?int
    {
        return isset($this->data['update_id']) ? (int) $this->data['update_id'] : null;
    }

    /**
     * The update type (message, callback_query, business_message, …).
     */
    public function type(): string
    {
        foreach (array_keys($this->data) as $key) {
            if ($key !== 'update_id') {
                return (string) $key;
            }
        }

        return 'unknown';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->data;
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * The message payload (regular, edited, channel or business message).
     *
     * @return array<string, mixed>|null
     */
    public function message(): ?array
    {
        foreach (['message', 'edited_message', 'channel_post', 'business_message', 'edited_business_message'] as $key) {
            if (isset($this->data[$key]) && is_array($this->data[$key])) {
                return $this->data[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function callbackQuery(): ?array
    {
        return is_array($this->data['callback_query'] ?? null) ? $this->data['callback_query'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function inlineQuery(): ?array
    {
        return is_array($this->data['inline_query'] ?? null) ? $this->data['inline_query'] : null;
    }

    /**
     * The text of the incoming message, or the data of a callback query.
     */
    public function text(): ?string
    {
        $message = $this->message();
        if ($message !== null && isset($message['text'])) {
            return (string) $message['text'];
        }

        $callback = $this->callbackQuery();
        if ($callback !== null && isset($callback['data'])) {
            return (string) $callback['data'];
        }

        return null;
    }

    /**
     * The chat id the update relates to.
     */
    public function chatId(): int|string|null
    {
        $message = $this->message();
        if ($message !== null) {
            return $message['chat']['id'] ?? null;
        }

        $callback = $this->callbackQuery();

        return $callback['message']['chat']['id'] ?? null;
    }

    /**
     * The user who triggered the update.
     *
     * @return array<string, mixed>|null
     */
    public function from(): ?array
    {
        $node = $this->message() ?? $this->callbackQuery() ?? $this->inlineQuery();

        return is_array($node['from'] ?? null) ? $node['from'] : null;
    }

    public function fromId(): ?int
    {
        $from = $this->from();

        return isset($from['id']) ? (int) $from['id'] : null;
    }

    public function isMessage(): bool
    {
        return $this->message() !== null;
    }

    public function isCallbackQuery(): bool
    {
        return $this->callbackQuery() !== null;
    }

    public function isCommand(): bool
    {
        $text = $this->text();

        return $text !== null && str_starts_with($text, '/');
    }

    /**
     * Whether this is a Telegram Business update / message.
     */
    public function isBusiness(): bool
    {
        return isset($this->data['business_message'])
            || isset($this->data['edited_business_message'])
            || isset($this->data['deleted_business_messages'])
            || isset($this->data['business_connection']);
    }

    public function businessConnectionId(): ?string
    {
        $id = $this->message()['business_connection_id']
            ?? $this->data['business_connection']['id']
            ?? null;

        return $id === null ? null : (string) $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
