<?php

declare(strict_types=1);

namespace TexHub\Telegram;

use ArrayAccess;

/**
 * Wraps the `result` of a Telegram API response. Behaves like a read-only
 * array when the result is an object (e.g. a Message).
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Response implements ArrayAccess
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly mixed $result = null,
        private readonly array $attributes = [],
    ) {
    }

    public static function from(mixed $result): self
    {
        return new self($result, is_array($result) ? $result : []);
    }

    /**
     * The raw result value (array for objects, bool/int/string for scalars).
     */
    public function value(): mixed
    {
        return $this->result;
    }

    public function boolean(): bool
    {
        return (bool) $this->result;
    }

    /** Message id, when the result is a Message. */
    public function messageId(): ?int
    {
        return isset($this->attributes['message_id']) ? (int) $this->attributes['message_id'] : null;
    }

    /** Chat id, when the result is a Message. */
    public function chatId(): int|string|null
    {
        return $this->attributes['chat']['id'] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $value = $this->attributes;
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
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}
