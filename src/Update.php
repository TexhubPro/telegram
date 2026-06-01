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
     * @return array<string, mixed>|null
     */
    public function preCheckoutQuery(): ?array
    {
        return is_array($this->data['pre_checkout_query'] ?? null) ? $this->data['pre_checkout_query'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function shippingQuery(): ?array
    {
        return is_array($this->data['shipping_query'] ?? null) ? $this->data['shipping_query'] : null;
    }

    /**
     * Successful payment info attached to a message, if any.
     *
     * @return array<string, mixed>|null
     */
    public function successfulPayment(): ?array
    {
        $payment = $this->message()['successful_payment'] ?? null;

        return is_array($payment) ? $payment : null;
    }

    public function isPreCheckoutQuery(): bool
    {
        return $this->preCheckoutQuery() !== null;
    }

    public function isShippingQuery(): bool
    {
        return $this->shippingQuery() !== null;
    }

    public function isSuccessfulPayment(): bool
    {
        return $this->successfulPayment() !== null;
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
     * The id of the incoming message.
     */
    public function messageId(): ?int
    {
        $id = $this->message()['message_id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    /**
     * Caption of a media message, if any.
     */
    public function caption(): ?string
    {
        $caption = $this->message()['caption'] ?? null;

        return $caption === null ? null : (string) $caption;
    }

    // ---- Attachments (everything that can arrive) -------------------------

    /**
     * Photo sizes array (largest last), or null.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function photo(): ?array
    {
        $photo = $this->message()['photo'] ?? null;

        return is_array($photo) ? $photo : null;
    }

    /**
     * The largest photo's file_id, or null.
     */
    public function photoFileId(): ?string
    {
        $photo = $this->photo();

        return $photo === null || $photo === [] ? null : (string) end($photo)['file_id'];
    }

    /** @return array<string, mixed>|null */
    public function video(): ?array
    {
        return $this->attachment('video');
    }

    /** @return array<string, mixed>|null */
    public function document(): ?array
    {
        return $this->attachment('document');
    }

    /** @return array<string, mixed>|null */
    public function audio(): ?array
    {
        return $this->attachment('audio');
    }

    /** @return array<string, mixed>|null */
    public function voice(): ?array
    {
        return $this->attachment('voice');
    }

    /** @return array<string, mixed>|null */
    public function animation(): ?array
    {
        return $this->attachment('animation');
    }

    /** @return array<string, mixed>|null */
    public function sticker(): ?array
    {
        return $this->attachment('sticker');
    }

    /** @return array<string, mixed>|null */
    public function videoNote(): ?array
    {
        return $this->attachment('video_note');
    }

    /** @return array{latitude: float, longitude: float}|null */
    public function location(): ?array
    {
        $loc = $this->attachment('location');

        return $loc === null ? null : ['latitude' => (float) $loc['latitude'], 'longitude' => (float) $loc['longitude']];
    }

    /** @return array<string, mixed>|null */
    public function contact(): ?array
    {
        return $this->attachment('contact');
    }

    /** @return array<string, mixed>|null */
    public function venue(): ?array
    {
        return $this->attachment('venue');
    }

    /** @return array<string, mixed>|null */
    public function poll(): ?array
    {
        return $this->attachment('poll');
    }

    /** @return array<string, mixed>|null */
    public function dice(): ?array
    {
        return $this->attachment('dice');
    }

    /**
     * The first file_id found on the message (photo, video, document, …).
     */
    public function fileId(): ?string
    {
        if (($id = $this->photoFileId()) !== null) {
            return $id;
        }

        foreach (['video', 'document', 'audio', 'voice', 'animation', 'sticker', 'video_note'] as $type) {
            $node = $this->attachment($type);
            if ($node !== null && isset($node['file_id'])) {
                return (string) $node['file_id'];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function attachment(string $key): ?array
    {
        $value = $this->message()[$key] ?? null;

        return is_array($value) ? $value : null;
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
