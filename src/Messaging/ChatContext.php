<?php

declare(strict_types=1);

namespace TexHub\Telegram\Messaging;

use TexHub\Telegram\Bot;
use TexHub\Telegram\Enums\ChatAction;
use TexHub\Telegram\InputFile;
use TexHub\Telegram\Response;

/**
 * A fluent helper bound to one chat. Start a message with {@see message()} or a
 * media helper, then chain and `->send()`.
 *
 * ```php
 * $bot->chat($chatId)->message('Hi')->send();
 * $bot->chat($chatId)->photo('/path.jpg')->caption('Look')->send();
 * $bot->chat($chatId)->action('typing');
 * ```
 */
final class ChatContext
{
    public function __construct(
        private readonly Bot $bot,
        private readonly int|string $chatId,
    ) {
    }

    public function id(): int|string
    {
        return $this->chatId;
    }

    public function message(string $text): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->message($text);
    }

    public function photo(string|InputFile $photo): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->photo($photo);
    }

    public function document(string|InputFile $document): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->document($document);
    }

    public function video(string|InputFile $video): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->video($video);
    }

    public function voice(string|InputFile $voice): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->voice($voice);
    }

    public function location(float $latitude, float $longitude): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->location($latitude, $longitude);
    }

    public function contact(string $phone, string $firstName): PendingMessage
    {
        return (new PendingMessage($this->bot, $this->chatId))->contact($phone, $firstName);
    }

    /**
     * Send an album (media group). Pass ready InputMedia items, e.g.
     * [['type' => 'photo', 'media' => $url, 'caption' => 'First']].
     *
     * @param array<int, array<string, mixed>> $media
     * @param array<string, mixed>             $options
     */
    public function mediaGroup(array $media, array $options = []): Response
    {
        return $this->bot->sendMediaGroup($this->chatId, $media, $options);
    }

    /**
     * Convenience album of photos (URLs / file_ids), with the caption on the first.
     *
     * @param array<int, string>   $photos
     * @param array<string, mixed> $options
     */
    public function photos(array $photos, ?string $caption = null, array $options = []): Response
    {
        $media = [];
        foreach (array_values($photos) as $i => $photo) {
            $item = ['type' => 'photo', 'media' => $photo];
            if ($i === 0 && $caption !== null) {
                $item['caption'] = $caption;
            }
            $media[] = $item;
        }

        return $this->mediaGroup($media, $options);
    }

    public function action(ChatAction|string $action): Response
    {
        return $this->bot->sendChatAction($this->chatId, $action);
    }

    public function deleteMessage(int $messageId): Response
    {
        return $this->bot->deleteMessage($this->chatId, $messageId);
    }
}
