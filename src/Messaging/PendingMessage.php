<?php

declare(strict_types=1);

namespace TexHub\Telegram\Messaging;

use TexHub\Telegram\Bot;
use TexHub\Telegram\Enums\ParseMode;
use TexHub\Telegram\InputFile;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\ReplyKeyboard;
use TexHub\Telegram\Response;

/**
 * A fluent, beginner-friendly message builder.
 *
 * ```php
 * $bot->chat($chatId)
 *     ->message('Hello <b>world</b>')
 *     ->html()
 *     ->keyboard(InlineKeyboard::make()->row(Button::callback('OK', 'ok')))
 *     ->send();
 *
 * $bot->chat($chatId)->photo('/path/pic.jpg')->caption('Nice')->send();
 * ```
 */
final class PendingMessage
{
    private string $type = 'text';
    private ?string $text = null;
    private string|InputFile|null $media = null;

    /** @var array<string, mixed> */
    private array $params = [];

    public function __construct(
        private readonly Bot $bot,
        private readonly int|string $chatId,
    ) {
    }

    public function message(string $text): self
    {
        $this->type = 'text';
        $this->text = $text;

        return $this;
    }

    public function photo(string|InputFile $photo): self
    {
        return $this->withMedia('photo', $photo);
    }

    public function document(string|InputFile $document): self
    {
        return $this->withMedia('document', $document);
    }

    public function video(string|InputFile $video): self
    {
        return $this->withMedia('video', $video);
    }

    public function audio(string|InputFile $audio): self
    {
        return $this->withMedia('audio', $audio);
    }

    public function voice(string|InputFile $voice): self
    {
        return $this->withMedia('voice', $voice);
    }

    public function animation(string|InputFile $animation): self
    {
        return $this->withMedia('animation', $animation);
    }

    public function sticker(string|InputFile $sticker): self
    {
        return $this->withMedia('sticker', $sticker);
    }

    public function location(float $latitude, float $longitude): self
    {
        $this->type = 'location';
        $this->params['latitude'] = $latitude;
        $this->params['longitude'] = $longitude;

        return $this;
    }

    public function contact(string $phone, string $firstName): self
    {
        $this->type = 'contact';
        $this->params['phone_number'] = $phone;
        $this->params['first_name'] = $firstName;

        return $this;
    }

    public function caption(string $caption): self
    {
        $this->text = $caption;

        return $this;
    }

    public function html(): self
    {
        $this->params['parse_mode'] = ParseMode::Html->value;

        return $this;
    }

    public function markdown(): self
    {
        $this->params['parse_mode'] = ParseMode::MarkdownV2->value;

        return $this;
    }

    public function parseMode(ParseMode|string $mode): self
    {
        $this->params['parse_mode'] = $mode instanceof ParseMode ? $mode->value : $mode;

        return $this;
    }

    public function keyboard(InlineKeyboard|array $keyboard): self
    {
        $this->params['reply_markup'] = $keyboard instanceof InlineKeyboard ? $keyboard->toArray() : $keyboard;

        return $this;
    }

    public function replyKeyboard(ReplyKeyboard|array $keyboard): self
    {
        $this->params['reply_markup'] = $keyboard instanceof ReplyKeyboard ? $keyboard->toArray() : $keyboard;

        return $this;
    }

    public function removeKeyboard(): self
    {
        $this->params['reply_markup'] = ReplyKeyboard::remove();

        return $this;
    }

    public function forceReply(?string $placeholder = null): self
    {
        $this->params['reply_markup'] = ReplyKeyboard::forceReply($placeholder);

        return $this;
    }

    public function replyTo(int $messageId): self
    {
        $this->params['reply_parameters'] = ['message_id' => $messageId];

        return $this;
    }

    public function silent(): self
    {
        $this->params['disable_notification'] = true;

        return $this;
    }

    public function protect(): self
    {
        $this->params['protect_content'] = true;

        return $this;
    }

    /**
     * Send on behalf of a Telegram Business connection.
     */
    public function business(string $connectionId): self
    {
        $this->params['business_connection_id'] = $connectionId;

        return $this;
    }

    /**
     * Merge any extra raw options.
     *
     * @param array<string, mixed> $options
     */
    public function options(array $options): self
    {
        $this->params = $options + $this->params;

        return $this;
    }

    /**
     * Send the message and return the API response.
     */
    public function send(): Response
    {
        return match ($this->type) {
            'photo', 'document', 'video', 'audio', 'voice', 'animation', 'sticker' => $this->sendMedia(),
            'location' => $this->bot->call('sendLocation', ['chat_id' => $this->chatId] + $this->params),
            'contact' => $this->bot->call('sendContact', ['chat_id' => $this->chatId] + $this->params),
            default => $this->bot->sendMessage($this->chatId, (string) $this->text, $this->params),
        };
    }

    private function withMedia(string $type, string|InputFile $media): self
    {
        $this->type = $type;
        $this->media = $media;

        return $this;
    }

    private function sendMedia(): Response
    {
        $params = $this->params;
        if ($this->text !== null && in_array($this->type, ['photo', 'video', 'document', 'audio', 'animation', 'voice'], true)) {
            $params['caption'] = $this->text;
        }

        return $this->bot->call('send' . ucfirst($this->type), [
            'chat_id' => $this->chatId,
            $this->type => $this->media,
        ] + $params);
    }
}
