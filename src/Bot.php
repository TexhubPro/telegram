<?php

declare(strict_types=1);

namespace TexHub\Telegram;

use TexHub\Telegram\Enums\ChatAction;
use TexHub\Telegram\Enums\ParseMode;
use TexHub\Telegram\Http\ApiClient;
use TexHub\Telegram\Http\CurlTransport;
use TexHub\Telegram\Http\Transport;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\ReplyKeyboard;
use TexHub\Telegram\Webhook\WebhookHandler;

/**
 * A Telegram bot client bound to a single token.
 *
 * Every Bot API method is reachable via {@see call()}; the most common ones
 * have typed helpers. All send methods accept a `business_connection_id`
 * (directly or via {@see asBusiness()}) for Telegram Business.
 *
 * @see https://core.telegram.org/bots/api
 */
final class Bot
{
    private readonly ApiClient $api;
    private readonly Transport $transport;

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
        private readonly ?string $businessConnectionId = null,
    ) {
        $this->transport = $transport ?? new CurlTransport($config->timeout);
        $this->api = new ApiClient($config, $this->transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    /**
     * Call any Bot API method.
     *
     * @param array<string, mixed> $params
     */
    public function call(string $method, array $params = []): Response
    {
        if ($this->businessConnectionId !== null && ! isset($params['business_connection_id'])) {
            $params['business_connection_id'] = $this->businessConnectionId;
        }

        return Response::from($this->api->call($method, $this->normalize($params)));
    }

    /**
     * Return a clone that sends on behalf of a Telegram Business connection.
     */
    public function asBusiness(string $businessConnectionId): self
    {
        return new self($this->config, $this->transport, $businessConnectionId);
    }

    public function getMe(): Response
    {
        return $this->call('getMe');
    }

    // ---- Messages ---------------------------------------------------------

    /**
     * @param array<string, mixed> $options
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): Response
    {
        return $this->call('sendMessage', $this->withParseMode([
            'chat_id' => $chatId,
            'text' => $text,
        ] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendPhoto(int|string $chatId, string|InputFile $photo, array $options = []): Response
    {
        return $this->call('sendPhoto', $this->withParseMode(['chat_id' => $chatId, 'photo' => $photo] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendDocument(int|string $chatId, string|InputFile $document, array $options = []): Response
    {
        return $this->call('sendDocument', $this->withParseMode(['chat_id' => $chatId, 'document' => $document] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendVideo(int|string $chatId, string|InputFile $video, array $options = []): Response
    {
        return $this->call('sendVideo', $this->withParseMode(['chat_id' => $chatId, 'video' => $video] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendAudio(int|string $chatId, string|InputFile $audio, array $options = []): Response
    {
        return $this->call('sendAudio', $this->withParseMode(['chat_id' => $chatId, 'audio' => $audio] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendVoice(int|string $chatId, string|InputFile $voice, array $options = []): Response
    {
        return $this->call('sendVoice', $this->withParseMode(['chat_id' => $chatId, 'voice' => $voice] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendAnimation(int|string $chatId, string|InputFile $animation, array $options = []): Response
    {
        return $this->call('sendAnimation', $this->withParseMode(['chat_id' => $chatId, 'animation' => $animation] + $options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendSticker(int|string $chatId, string|InputFile $sticker, array $options = []): Response
    {
        return $this->call('sendSticker', ['chat_id' => $chatId, 'sticker' => $sticker] + $options);
    }

    /**
     * @param array<int, array<string, mixed>> $media
     * @param array<string, mixed>             $options
     */
    public function sendMediaGroup(int|string $chatId, array $media, array $options = []): Response
    {
        return $this->call('sendMediaGroup', ['chat_id' => $chatId, 'media' => $media] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendLocation(int|string $chatId, float $latitude, float $longitude, array $options = []): Response
    {
        return $this->call('sendLocation', ['chat_id' => $chatId, 'latitude' => $latitude, 'longitude' => $longitude] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendContact(int|string $chatId, string $phoneNumber, string $firstName, array $options = []): Response
    {
        return $this->call('sendContact', ['chat_id' => $chatId, 'phone_number' => $phoneNumber, 'first_name' => $firstName] + $options);
    }

    /**
     * @param array<int, string>   $pollOptions
     * @param array<string, mixed> $options
     */
    public function sendPoll(int|string $chatId, string $question, array $pollOptions, array $options = []): Response
    {
        return $this->call('sendPoll', ['chat_id' => $chatId, 'question' => $question, 'options' => $pollOptions] + $options);
    }

    public function sendChatAction(int|string $chatId, ChatAction|string $action, array $options = []): Response
    {
        return $this->call('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action instanceof ChatAction ? $action->value : $action,
        ] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): Response
    {
        return $this->call('editMessageText', $this->withParseMode([
            'chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text,
        ] + $options));
    }

    public function editMessageReplyMarkup(int|string $chatId, int $messageId, InlineKeyboard|array $markup): Response
    {
        return $this->call('editMessageReplyMarkup', [
            'chat_id' => $chatId, 'message_id' => $messageId,
            'reply_markup' => $markup instanceof InlineKeyboard ? $markup->toArray() : $markup,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function editMessageCaption(int|string $chatId, int $messageId, ?string $caption = null, array $options = []): Response
    {
        return $this->call('editMessageCaption', $this->withParseMode([
            'chat_id' => $chatId, 'message_id' => $messageId, 'caption' => $caption,
        ] + $options));
    }

    /**
     * Replace the media (photo/video/document/audio/animation) of a message.
     *
     * @param array<string, mixed> $media An InputMedia object, e.g.
     *        ['type' => 'photo', 'media' => $urlOrAttach, 'caption' => '...'].
     * @param array<string, mixed> $options
     */
    public function editMessageMedia(int|string $chatId, int $messageId, array $media, array $options = []): Response
    {
        return $this->call('editMessageMedia', ['chat_id' => $chatId, 'message_id' => $messageId, 'media' => $media] + $options);
    }

    public function deleteMessage(int|string $chatId, int $messageId): Response
    {
        return $this->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    /**
     * Delete multiple messages at once.
     *
     * @param array<int, int> $messageIds
     */
    public function deleteMessages(int|string $chatId, array $messageIds): Response
    {
        return $this->call('deleteMessages', ['chat_id' => $chatId, 'message_ids' => $messageIds]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendVideoNote(int|string $chatId, string|InputFile $videoNote, array $options = []): Response
    {
        return $this->call('sendVideoNote', ['chat_id' => $chatId, 'video_note' => $videoNote] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendDice(int|string $chatId, array $options = []): Response
    {
        return $this->call('sendDice', ['chat_id' => $chatId] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function sendVenue(int|string $chatId, float $latitude, float $longitude, string $title, string $address, array $options = []): Response
    {
        return $this->call('sendVenue', [
            'chat_id' => $chatId, 'latitude' => $latitude, 'longitude' => $longitude,
            'title' => $title, 'address' => $address,
        ] + $options);
    }

    /**
     * React to a message with emoji(s) (Bot API 7.0+).
     *
     * @param array<int, string>|string $emoji One or more emoji, e.g. '👍' or ['👍','🔥'].
     */
    public function setMessageReaction(int|string $chatId, int $messageId, array|string $emoji, bool $isBig = false): Response
    {
        $emojis = is_array($emoji) ? $emoji : [$emoji];
        $reaction = array_map(static fn (string $e) => ['type' => 'emoji', 'emoji' => $e], $emojis);

        return $this->call('setMessageReaction', [
            'chat_id' => $chatId, 'message_id' => $messageId, 'reaction' => $reaction, 'is_big' => $isBig,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pinChatMessage(int|string $chatId, int $messageId, array $options = []): Response
    {
        return $this->call('pinChatMessage', ['chat_id' => $chatId, 'message_id' => $messageId] + $options);
    }

    public function unpinChatMessage(int|string $chatId, ?int $messageId = null): Response
    {
        return $this->call('unpinChatMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function forwardMessage(int|string $chatId, int|string $fromChatId, int $messageId, array $options = []): Response
    {
        return $this->call('forwardMessage', ['chat_id' => $chatId, 'from_chat_id' => $fromChatId, 'message_id' => $messageId] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function copyMessage(int|string $chatId, int|string $fromChatId, int $messageId, array $options = []): Response
    {
        return $this->call('copyMessage', ['chat_id' => $chatId, 'from_chat_id' => $fromChatId, 'message_id' => $messageId] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function answerCallbackQuery(string $callbackQueryId, array $options = []): Response
    {
        return $this->call('answerCallbackQuery', ['callback_query_id' => $callbackQueryId] + $options);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     * @param array<string, mixed>             $options
     */
    public function answerInlineQuery(string $inlineQueryId, array $results, array $options = []): Response
    {
        return $this->call('answerInlineQuery', ['inline_query_id' => $inlineQueryId, 'results' => $results] + $options);
    }

    // ---- Payments (Invoices & Telegram Stars) -----------------------------

    /**
     * Send an invoice. For Telegram Stars use currency "XTR" and an empty provider token.
     *
     * @param array<int, array{label: string, amount: int}> $prices
     * @param array<string, mixed>                           $options
     */
    public function sendInvoice(int|string $chatId, string $title, string $description, string $payload, string $currency, array $prices, array $options = []): Response
    {
        return $this->call('sendInvoice', [
            'chat_id' => $chatId, 'title' => $title, 'description' => $description,
            'payload' => $payload, 'currency' => $currency, 'prices' => $prices,
        ] + $options);
    }

    /**
     * Create an invoice link (returns the URL string in the result).
     *
     * @param array<int, array{label: string, amount: int}> $prices
     * @param array<string, mixed>                           $options
     */
    public function createInvoiceLink(string $title, string $description, string $payload, string $currency, array $prices, array $options = []): Response
    {
        return $this->call('createInvoiceLink', [
            'title' => $title, 'description' => $description, 'payload' => $payload,
            'currency' => $currency, 'prices' => $prices,
        ] + $options);
    }

    /**
     * @param array<string, mixed> $options shipping_options OR error_message
     */
    public function answerShippingQuery(string $shippingQueryId, bool $ok, array $options = []): Response
    {
        return $this->call('answerShippingQuery', ['shipping_query_id' => $shippingQueryId, 'ok' => $ok] + $options);
    }

    /**
     * @param array<string, mixed> $options error_message when $ok is false
     */
    public function answerPreCheckoutQuery(string $preCheckoutQueryId, bool $ok, array $options = []): Response
    {
        return $this->call('answerPreCheckoutQuery', ['pre_checkout_query_id' => $preCheckoutQueryId, 'ok' => $ok] + $options);
    }

    /**
     * Refund a successful Telegram Stars payment.
     */
    public function refundStarPayment(int $userId, string $telegramPaymentChargeId): Response
    {
        return $this->call('refundStarPayment', ['user_id' => $userId, 'telegram_payment_charge_id' => $telegramPaymentChargeId]);
    }

    // ---- Games ------------------------------------------------------------

    /**
     * @param array<string, mixed> $options
     */
    public function sendGame(int|string $chatId, string $gameShortName, array $options = []): Response
    {
        return $this->call('sendGame', ['chat_id' => $chatId, 'game_short_name' => $gameShortName] + $options);
    }

    /**
     * @param array<string, mixed> $options chat_id+message_id OR inline_message_id, force, disable_edit_message
     */
    public function setGameScore(int $userId, int $score, array $options = []): Response
    {
        return $this->call('setGameScore', ['user_id' => $userId, 'score' => $score] + $options);
    }

    /**
     * @param array<string, mixed> $options chat_id+message_id OR inline_message_id
     */
    public function getGameHighScores(int $userId, array $options = []): Response
    {
        return $this->call('getGameHighScores', ['user_id' => $userId] + $options);
    }

    // ---- Chat -------------------------------------------------------------

    public function getChat(int|string $chatId): Response
    {
        return $this->call('getChat', ['chat_id' => $chatId]);
    }

    public function getChatMember(int|string $chatId, int $userId): Response
    {
        return $this->call('getChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function banChatMember(int|string $chatId, int $userId, array $options = []): Response
    {
        return $this->call('banChatMember', ['chat_id' => $chatId, 'user_id' => $userId] + $options);
    }

    public function unbanChatMember(int|string $chatId, int $userId, array $options = []): Response
    {
        return $this->call('unbanChatMember', ['chat_id' => $chatId, 'user_id' => $userId] + $options);
    }

    /**
     * @param array<string, mixed> $permissions ChatPermissions object.
     * @param array<string, mixed> $options
     */
    public function restrictChatMember(int|string $chatId, int $userId, array $permissions, array $options = []): Response
    {
        return $this->call('restrictChatMember', ['chat_id' => $chatId, 'user_id' => $userId, 'permissions' => $permissions] + $options);
    }

    /**
     * @param array<string, mixed> $rights e.g. ['can_manage_chat' => true, 'can_delete_messages' => true]
     */
    public function promoteChatMember(int|string $chatId, int $userId, array $rights = []): Response
    {
        return $this->call('promoteChatMember', ['chat_id' => $chatId, 'user_id' => $userId] + $rights);
    }

    public function getChatAdministrators(int|string $chatId): Response
    {
        return $this->call('getChatAdministrators', ['chat_id' => $chatId]);
    }

    public function getChatMemberCount(int|string $chatId): Response
    {
        return $this->call('getChatMemberCount', ['chat_id' => $chatId]);
    }

    public function leaveChat(int|string $chatId): Response
    {
        return $this->call('leaveChat', ['chat_id' => $chatId]);
    }

    public function setChatTitle(int|string $chatId, string $title): Response
    {
        return $this->call('setChatTitle', ['chat_id' => $chatId, 'title' => $title]);
    }

    public function setChatDescription(int|string $chatId, string $description): Response
    {
        return $this->call('setChatDescription', ['chat_id' => $chatId, 'description' => $description]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createChatInviteLink(int|string $chatId, array $options = []): Response
    {
        return $this->call('createChatInviteLink', ['chat_id' => $chatId] + $options);
    }

    public function exportChatInviteLink(int|string $chatId): Response
    {
        return $this->call('exportChatInviteLink', ['chat_id' => $chatId]);
    }

    public function unpinAllChatMessages(int|string $chatId): Response
    {
        return $this->call('unpinAllChatMessages', ['chat_id' => $chatId]);
    }

    // ---- Files ------------------------------------------------------------

    public function getFile(string $fileId): Response
    {
        return $this->call('getFile', ['file_id' => $fileId]);
    }

    /**
     * Download a file's bytes by file id.
     */
    public function downloadFile(string $fileId): string
    {
        $path = (string) $this->getFile($fileId)->get('file_path');

        return $this->api->download($path);
    }

    /**
     * Download a file and save it to disk; returns the destination path.
     */
    public function downloadFileTo(string $fileId, string $destination): string
    {
        file_put_contents($destination, $this->downloadFile($fileId));

        return $destination;
    }

    // ---- Webhook & updates ------------------------------------------------

    /**
     * @param array<string, mixed> $options e.g. ['secret_token' => '...', 'allowed_updates' => [...], 'drop_pending_updates' => true]
     */
    public function setWebhook(string $url, array $options = []): Response
    {
        $options['secret_token'] ??= $this->config->webhookSecret;

        return $this->call('setWebhook', ['url' => $url] + $options);
    }

    public function deleteWebhook(bool $dropPendingUpdates = false): Response
    {
        return $this->call('deleteWebhook', ['drop_pending_updates' => $dropPendingUpdates]);
    }

    /**
     * Alias of {@see deleteWebhook()} — remove the webhook integration.
     */
    public function unsetWebhook(bool $dropPendingUpdates = false): Response
    {
        return $this->deleteWebhook($dropPendingUpdates);
    }

    public function getWebhookInfo(): Response
    {
        return $this->call('getWebhookInfo');
    }

    // ---- Bot settings -----------------------------------------------------

    /**
     * @param array<int, array{command: string, description: string}> $commands
     * @param array<string, mixed>                                    $options
     */
    public function setMyCommands(array $commands, array $options = []): Response
    {
        return $this->call('setMyCommands', ['commands' => $commands] + $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function deleteMyCommands(array $options = []): Response
    {
        return $this->call('deleteMyCommands', $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getMyCommands(array $options = []): Response
    {
        return $this->call('getMyCommands', $options);
    }

    public function setMyName(string $name, ?string $languageCode = null): Response
    {
        return $this->call('setMyName', ['name' => $name, 'language_code' => $languageCode]);
    }

    public function setMyDescription(string $description, ?string $languageCode = null): Response
    {
        return $this->call('setMyDescription', ['description' => $description, 'language_code' => $languageCode]);
    }

    public function setMyShortDescription(string $shortDescription, ?string $languageCode = null): Response
    {
        return $this->call('setMyShortDescription', ['short_description' => $shortDescription, 'language_code' => $languageCode]);
    }

    /**
     * Long-polling: fetch pending updates as {@see Update} objects.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int, Update>
     */
    public function getUpdates(array $options = []): array
    {
        $result = $this->api->call('getUpdates', $this->normalize($options));

        return array_map(
            static fn (array $u) => new Update($u),
            is_array($result) ? $result : [],
        );
    }

    // ---- Telegram Business ------------------------------------------------

    public function getBusinessConnection(string $businessConnectionId): Response
    {
        return $this->call('getBusinessConnection', ['business_connection_id' => $businessConnectionId]);
    }

    // ---- Webhook helper ---------------------------------------------------

    public function webhooks(): WebhookHandler
    {
        return new WebhookHandler($this->config);
    }

    // ---- Internals --------------------------------------------------------

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function withParseMode(array $params): array
    {
        if ($this->config->defaultParseMode !== null && ! isset($params['parse_mode'])) {
            $params['parse_mode'] = $this->config->defaultParseMode;
        }

        return $params;
    }

    /**
     * Normalize keyboard builders and enums in params.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function normalize(array $params): array
    {
        if (isset($params['reply_markup'])) {
            $markup = $params['reply_markup'];
            if ($markup instanceof InlineKeyboard || $markup instanceof ReplyKeyboard) {
                $params['reply_markup'] = $markup->toArray();
            }
        }

        if (isset($params['parse_mode']) && $params['parse_mode'] instanceof ParseMode) {
            $params['parse_mode'] = $params['parse_mode']->value;
        }

        return $params;
    }
}
