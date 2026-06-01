<?php

declare(strict_types=1);

namespace TexHub\Telegram\Keyboard;

/**
 * Builder for an inline keyboard (`inline_keyboard`).
 *
 * ```php
 * InlineKeyboard::make()
 *     ->row(Button::callback('Yes', 'yes'), Button::callback('No', 'no'))
 *     ->row(Button::url('Open', 'https://texhub.pro'));
 * ```
 */
final class InlineKeyboard
{
    /** @var array<int, array<int, array<string, mixed>>> */
    private array $rows = [];

    public static function make(): self
    {
        return new self();
    }

    /**
     * @param array<string, mixed> ...$buttons
     */
    public function row(array ...$buttons): self
    {
        $this->rows[] = array_values($buttons);

        return $this;
    }

    /**
     * Add buttons split into rows of $perRow.
     *
     * @param array<int, array<string, mixed>> $buttons
     */
    public function buttons(array $buttons, int $perRow = 1): self
    {
        foreach (array_chunk($buttons, max(1, $perRow)) as $chunk) {
            $this->rows[] = $chunk;
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['inline_keyboard' => $this->rows];
    }
}
