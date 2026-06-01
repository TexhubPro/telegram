<?php

declare(strict_types=1);

namespace TexHub\Telegram\Handler;

use ReflectionMethod;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Enums\ChatAction;
use TexHub\Telegram\InputFile;
use TexHub\Telegram\Messaging\ChatContext;
use TexHub\Telegram\Response;
use TexHub\Telegram\Update;

/**
 * Base webhook handler: extend it, override the `on*` / `command*` methods, and
 * let it route every incoming update for you. Inside your handlers you have
 * `$this->bot`, `$this->update`, and reply helpers bound to the current chat.
 *
 * ```php
 * class MyBotHandler extends UpdateHandler
 * {
 *     // "/start" — the method name IS the command. $payload is the text after it,
 *     // e.g. for "/start REF123" you get the referral code "REF123".
 *     public function start(string $payload = ''): void
 *     {
 *         $this->chat->message("Welcome! 👋 Invite code: {$payload}")->send();
 *     }
 *
 *     public function onText(string $text): void
 *     {
 *         $this->chat->message('You said: ' . $text)->send();
 *     }
 *
 *     public function onContact(array $contact): void
 *     {
 *         $this->chat->message('Phone: ' . $contact['phone_number'])->send();
 *     }
 *
 *     public function onCallbackQuery(array $callbackQuery): void
 *     {
 *         $this->answerCallback('Got it');
 *     }
 * }
 *
 * // In your controller:
 * (new MyBotHandler)->handleRequest($bot, $request->getContent(), $request->header('X-Telegram-Bot-Api-Secret-Token'));
 * ```
 */
abstract class UpdateHandler
{
    protected Bot $bot;
    protected Update $update;

    /**
     * A fluent helper bound to the current chat: `$this->chat->message('hi')->send()`.
     */
    protected ChatContext $chat;

    /**
     * Verify the secret token, parse the body and dispatch.
     */
    public function handleRequest(Bot $bot, string|array $body, ?string $secretHeader = null): void
    {
        $bot->webhooks()->assertValid($secretHeader);

        $this->handle($bot, $bot->webhooks()->parse($body));
    }

    /**
     * Route an already-parsed update to the right handler method.
     */
    public function handle(Bot $bot, Update $update): void
    {
        $this->bot = $bot;
        $this->update = $update;

        $chatId = $update->chatId();
        if ($chatId !== null) {
            $this->chat = $bot->chat($chatId);
        }

        if ($update->isCallbackQuery()) {
            $this->onCallbackQuery((array) $update->callbackQuery());

            return;
        }

        if ($update->inlineQuery() !== null) {
            $this->onInlineQuery((array) $update->inlineQuery());

            return;
        }

        if ($update->preCheckoutQuery() !== null) {
            $this->onPreCheckoutQuery((array) $update->preCheckoutQuery());

            return;
        }

        if ($update->isMessage()) {
            $this->dispatchMessage();

            return;
        }

        $this->onOther();
    }

    private function dispatchMessage(): void
    {
        // Attachments first.
        if ($this->update->photo() !== null) {
            $this->onPhoto();

            return;
        }
        foreach (['video' => 'onVideo', 'document' => 'onDocument', 'voice' => 'onVoice',
                  'audio' => 'onAudio', 'animation' => 'onAnimation', 'sticker' => 'onSticker'] as $type => $method) {
            if ($this->update->{$type}() !== null) {
                $this->{$method}();

                return;
            }
        }
        if (($location = $this->update->location()) !== null) {
            $this->onLocation($location);

            return;
        }
        if (($contact = $this->update->contact()) !== null) {
            $this->onContact($contact);

            return;
        }

        $text = $this->update->text();

        // Commands: "/start payload" → start("payload") or commandStart("payload")
        // or onCommand("start", "payload"). The plain method name is the easiest.
        if ($text !== null && str_starts_with($text, '/')) {
            $parts = explode(' ', ltrim($text, '/'), 2);
            $command = strtolower(strtok($parts[0], '@') ?: '');
            $payload = $parts[1] ?? '';

            $studly = str_replace('_', '', ucwords($command, '_'));
            foreach (['command' . $studly, $command, lcfirst($studly)] as $candidate) {
                if ($this->isHandlerMethod($candidate)) {
                    $this->{$candidate}($payload);

                    return;
                }
            }

            $this->onCommand($command, $payload);

            return;
        }

        if ($text !== null) {
            $this->onText($text);

            return;
        }

        $this->onMessage();
    }

    // ---- Override these in your handler -----------------------------------

    protected function onText(string $text): void
    {
        $this->onMessage();
    }

    protected function onCommand(string $command, string $payload): void
    {
    }

    protected function onMessage(): void
    {
    }

    protected function onPhoto(): void
    {
    }

    protected function onVideo(): void
    {
    }

    protected function onDocument(): void
    {
    }

    protected function onVoice(): void
    {
    }

    protected function onAudio(): void
    {
    }

    protected function onAnimation(): void
    {
    }

    protected function onSticker(): void
    {
    }

    /**
     * @param array{latitude: float, longitude: float} $location
     */
    protected function onLocation(array $location): void
    {
    }

    /**
     * @param array<string, mixed> $contact
     */
    protected function onContact(array $contact): void
    {
    }

    /**
     * @param array<string, mixed> $callbackQuery
     */
    protected function onCallbackQuery(array $callbackQuery): void
    {
    }

    /**
     * @param array<string, mixed> $inlineQuery
     */
    protected function onInlineQuery(array $inlineQuery): void
    {
    }

    /**
     * @param array<string, mixed> $preCheckoutQuery
     */
    protected function onPreCheckoutQuery(array $preCheckoutQuery): void
    {
    }

    protected function onOther(): void
    {
    }

    // ---- Reply helpers (bound to the current chat) ------------------------

    /**
     * Only treat a method as a command handler when it is defined in your
     * subclass (so "/reply" or "/handle" can't trigger base methods).
     */
    private function isHandlerMethod(string $method): bool
    {
        if ($method === '' || ! method_exists($this, $method)) {
            return false;
        }

        return (new ReflectionMethod($this, $method))->getDeclaringClass()->getName() !== self::class;
    }

    protected function chatId(): int|string|null
    {
        return $this->update->chatId();
    }

    protected function fromId(): ?int
    {
        return $this->update->fromId();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function reply(string $text, array $options = []): Response
    {
        return $this->bot->sendMessage((string) $this->chatId(), $text, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function replyPhoto(string|InputFile $photo, array $options = []): Response
    {
        return $this->bot->sendPhoto((string) $this->chatId(), $photo, $options);
    }

    protected function replyChatAction(ChatAction|string $action): Response
    {
        return $this->bot->sendChatAction((string) $this->chatId(), $action);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function answerCallback(string $text = '', array $options = []): Response
    {
        $id = (string) ($this->update->callbackQuery()['id'] ?? '');

        return $this->bot->answerCallbackQuery($id, ['text' => $text] + $options);
    }
}
