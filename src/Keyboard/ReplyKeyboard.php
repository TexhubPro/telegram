<?php

declare(strict_types=1);

namespace TexHub\Telegram\Keyboard;

/**
 * Builder for a reply keyboard (`reply_keyboard_markup`) and helpers for
 * removing the keyboard or forcing a reply.
 */
final class ReplyKeyboard
{
    /** @var array<int, array<int, array<string, mixed>>> */
    private array $rows = [];

    private bool $resize = true;
    private bool $oneTime = false;
    private bool $selective = false;
    private ?string $placeholder = null;

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed>|string ...$buttons
     */
    public function row(array|string ...$buttons): self
    {
        $this->rows[] = array_map(
            static fn ($b) => is_string($b) ? ['text' => $b] : $b,
            array_values($buttons),
        );

        return $this;
    }

    public function resize(bool $resize = true): self
    {
        $this->resize = $resize;

        return $this;
    }

    public function oneTime(bool $oneTime = true): self
    {
        $this->oneTime = $oneTime;

        return $this;
    }

    public function selective(bool $selective = true): self
    {
        $this->selective = $selective;

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $markup = [
            'keyboard' => $this->rows,
            'resize_keyboard' => $this->resize,
            'one_time_keyboard' => $this->oneTime,
            'selective' => $this->selective,
        ];

        if ($this->placeholder !== null) {
            $markup['input_field_placeholder'] = $this->placeholder;
        }

        return $markup;
    }

    /**
     * Markup that removes the custom keyboard.
     *
     * @return array<string, mixed>
     */
    public static function remove(bool $selective = false): array
    {
        return ['remove_keyboard' => true, 'selective' => $selective];
    }

    /**
     * Markup that forces the user to reply.
     *
     * @return array<string, mixed>
     */
    public static function forceReply(?string $placeholder = null, bool $selective = false): array
    {
        $markup = ['force_reply' => true, 'selective' => $selective];
        if ($placeholder !== null) {
            $markup['input_field_placeholder'] = $placeholder;
        }

        return $markup;
    }
}
