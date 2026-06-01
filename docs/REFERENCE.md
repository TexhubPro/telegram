# TexHub · Telegram — Full Method Reference

[Русская версия](REFERENCE.ru.md) · [← Back to README](../README.md)

A copy-paste cookbook of **every** method in `texhub/telegram`, from installation to
sending, receiving, webhooks, payments, games and Telegram Business — each with an
example call and an example response.

> Every snippet is ready to paste. The imports below cover all examples on this page.

```php
use TexHub\Telegram\Telegram;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\InputFile;
use TexHub\Telegram\Enums\ChatAction;
use TexHub\Telegram\Enums\ParseMode;
use TexHub\Telegram\Keyboard\Button;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\ReplyKeyboard;
use TexHub\Telegram\Exceptions\ApiException;
use TexHub\Telegram\Exceptions\TelegramException;
```

---

## Table of contents

1. [Installation](#1-installation)
2. [Create / connect a bot](#2-create--connect-a-bot)
3. [getMe](#3-getme)
4. [Sending messages](#4-sending-messages)
5. [Media & files](#5-media--files)
6. [Keyboards & buttons](#6-keyboards--buttons)
7. [Editing & deleting](#7-editing--deleting)
8. [Reactions, pinning](#8-reactions--pinning)
9. [Downloading files](#9-downloading-files)
10. [Chat administration](#10-chat-administration)
11. [Bot settings & commands](#11-bot-settings--commands)
12. [Payments (invoices & Stars)](#12-payments-invoices--stars)
13. [Games](#13-games)
14. [Webhooks](#14-webhooks)
15. [Receiving updates (the Update object)](#15-receiving-updates-the-update-object)
16. [Telegram Business](#16-telegram-business)
17. [Multi-tenant (many bots)](#17-multi-tenant-many-bots)
18. [Any other API method (call)](#18-any-other-api-method-call)
19. [Error handling](#19-error-handling)
20. [Laravel](#20-laravel)
21. [Testing](#21-testing)

---

## 1. Installation

```bash
composer require texhub/telegram
```

Requirements: PHP ≥ 8.2 with `curl`, `json`, `hash`.

---

## 2. Create / connect a bot

```php
// Simplest — a single bot from a token (from @BotFather):
$bot = Telegram::bot('123456:ABC-YourBotToken');

// With options:
$bot = new Bot(new Config(
    token: '123456:ABC-YourBotToken',
    webhookSecret: 'my-webhook-secret',   // optional (recommended)
    defaultParseMode: 'HTML',             // optional default for text
    timeout: 30,
));
```

---

## 3. getMe

```php
$me = $bot->getMe();
echo $me->get('username');   // your bot's @username
```

**Example response** (`$me->toArray()`):

```json
{ "id": 123456, "is_bot": true, "first_name": "My Bot", "username": "my_bot",
  "can_join_groups": true, "can_read_all_group_messages": false, "supports_inline_queries": false }
```

---

## 4. Sending messages

```php
$msg = $bot->sendMessage($chatId, 'Hello, <b>world</b>!', ['parse_mode' => 'HTML']);

echo $msg->messageId();   // 42
echo $msg->chatId();      // 99999
```

**Example response:**

```json
{ "message_id": 42, "from": { "id": 123456, "is_bot": true, "username": "my_bot" },
  "chat": { "id": 99999, "type": "private" }, "date": 1717250000, "text": "Hello, world!" }
```

Common options: `reply_markup`, `reply_to_message_id`, `disable_notification`,
`protect_content`, `message_thread_id`, `link_preview_options`, `entities`, `business_connection_id`.

```php
// Reply to a message, silently, without link preview:
$bot->sendMessage($chatId, 'A reply', [
    'reply_parameters' => ['message_id' => 42],
    'disable_notification' => true,
    'link_preview_options' => ['is_disabled' => true],
]);
```

---

## 5. Media & files

Each media value can be a **file_id**, a **public URL**, or a local **`InputFile`** (multipart upload).

```php
$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'A photo']);
$bot->sendPhoto($chatId, InputFile::fromPath('/path/local.jpg'), ['caption' => 'Local upload']);
$bot->sendDocument($chatId, InputFile::fromPath('/path/invoice.pdf'));
$bot->sendVideo($chatId, 'https://example.com/clip.mp4', ['caption' => 'Clip']);
$bot->sendAudio($chatId, InputFile::fromPath('/path/song.mp3'));
$bot->sendVoice($chatId, InputFile::fromPath('/path/voice.ogg'));
$bot->sendAnimation($chatId, 'https://example.com/anim.gif');
$bot->sendVideoNote($chatId, InputFile::fromPath('/path/note.mp4'));
$bot->sendSticker($chatId, 'CAACAgIAAxkBA...'); // sticker file_id

// Album / media group:
$bot->sendMediaGroup($chatId, [
    ['type' => 'photo', 'media' => 'https://example.com/1.jpg', 'caption' => 'One'],
    ['type' => 'photo', 'media' => 'https://example.com/2.jpg'],
]);

// Location, venue, contact, poll, dice:
$bot->sendLocation($chatId, 38.5598, 68.7870);
$bot->sendVenue($chatId, 38.5598, 68.7870, 'Office', 'Dushanbe, Rudaki ave.');
$bot->sendContact($chatId, '992900123456', 'Ali');
$bot->sendPoll($chatId, 'Your favorite?', ['PHP', 'JS', 'Go']);
$bot->sendDice($chatId, ['emoji' => '🎲']);

// "typing…" and other chat actions:
$bot->sendChatAction($chatId, ChatAction::Typing);
$bot->sendChatAction($chatId, ChatAction::UploadPhoto);
```

**Example response (sendPhoto):**

```json
{ "message_id": 43, "chat": { "id": 99999, "type": "private" }, "date": 1717250100,
  "photo": [ { "file_id": "AgACAg...", "width": 90, "height": 90 },
             { "file_id": "AgACAg...", "width": 1280, "height": 1280 } ], "caption": "A photo" }
```

### Caption, albums, voice & chat actions

```php
// Photo WITH a caption (the media "text"):
$bot->chat($chatId)->photo('https://x/p.jpg')->caption('Look at this')->send();
$bot->sendPhoto($chatId, 'https://x/p.jpg', ['caption' => 'Look at this']);

// Album: several photos, one caption on the first:
$bot->chat($chatId)->photos(['https://x/1.jpg', 'https://x/2.jpg', 'https://x/3.jpg'], 'My album');
// or a mixed media group:
$bot->chat($chatId)->mediaGroup([
    ['type' => 'photo', 'media' => 'https://x/1.jpg', 'caption' => 'First'],
    ['type' => 'video', 'media' => 'https://x/clip.mp4'],
]);

// Voice — send and receive:
$bot->chat($chatId)->voice(InputFile::fromPath('/path/voice.ogg'))->send();
// incoming: $update->voice()  → ['file_id' => ..., 'duration' => ...];  $update->fileId()

// Chat actions ("typing…", "uploading photo…") — call one and the user sees the indicator.
// Simplest: named methods on the chat:
$bot->chat($chatId)->typing();             // shows "typing…"
$bot->chat($chatId)->uploadingPhoto();     // "sending photo…"
$bot->chat($chatId)->uploadingVideo();
$bot->chat($chatId)->recordingVoice();
$bot->chat($chatId)->uploadingDocument();
$bot->chat($chatId)->choosingSticker();
$bot->chat($chatId)->findingLocation();

// Or explicitly:
$bot->sendChatAction($chatId, ChatAction::Typing);
$bot->chat($chatId)->action('upload_voice');
// inside a handler: $this->chat->typing();
```

> The action lasts ~5 seconds (or until you send a message). Call it again to keep it showing.

> A Telegram message holds **either** `text` (text message) **or** `caption` (media message).
> So for an incoming photo/video read `$update->caption()`; for plain text read `$update->text()`.

---

## 6. Keyboards & buttons

### Inline keyboard

```php
$bot->sendMessage($chatId, 'Choose an option:', [
    'reply_markup' => InlineKeyboard::make()
        ->row(Button::callback('✅ Yes', 'yes'), Button::callback('❌ No', 'no'))
        ->row(Button::url('🌐 Website', 'https://texhub.pro'))
        ->row(Button::webApp('🚀 Open app', 'https://app.texhub.pro'))      // Mini App
        ->row(Button::copyText('📋 Copy code', 'PROMO-2026'))               // copy to clipboard
        ->row(Button::switchInline('Share', 'query'))
        ->row(Button::loginUrl('🔐 Login', 'https://texhub.pro/tg-login')),
]);
```

All inline buttons: `callback`, `url`, `webApp`, `copyText`, `loginUrl`,
`switchInline`, `switchInlineCurrent`, `pay`, `callbackGame`.

### Reply keyboard

```php
$bot->sendMessage($chatId, 'Share details:', [
    'reply_markup' => ReplyKeyboard::make()
        ->row(Button::requestContact('📱 Send contact'), Button::requestLocation('📍 Send location'))
        ->row(Button::requestPoll('📊 Create poll'))
        ->row(Button::requestUsers('👥 Pick users', requestId: 1))
        ->row(Button::requestChat('💬 Pick chat', requestId: 2))
        ->resize()->oneTime()->persistent()->placeholder('Pick an option…'),
]);

// Remove the keyboard:
$bot->sendMessage($chatId, 'Done', ['reply_markup' => ReplyKeyboard::remove()]);

// Force the user to reply:
$bot->sendMessage($chatId, 'Your name?', ['reply_markup' => ReplyKeyboard::forceReply('Type here…')]);
```

---

## 7. Editing & deleting

```php
$bot->editMessageText($chatId, $messageId, 'Updated text', ['parse_mode' => 'HTML']);
$bot->editMessageCaption($chatId, $messageId, 'New caption');
$bot->editMessageMedia($chatId, $messageId, ['type' => 'photo', 'media' => 'https://example.com/new.jpg']);
$bot->editMessageReplyMarkup($chatId, $messageId, InlineKeyboard::make()->row(Button::callback('OK', 'ok')));

$bot->deleteMessage($chatId, $messageId);
$bot->deleteMessages($chatId, [10, 11, 12]);

$bot->forwardMessage($chatId, $fromChatId, $messageId);
$bot->copyMessage($chatId, $fromChatId, $messageId);
```

**Example response (deleteMessage):** `true`

---

## 8. Reactions & pinning

```php
$bot->setMessageReaction($chatId, $messageId, '👍');
$bot->setMessageReaction($chatId, $messageId, ['🔥', '❤️'], isBig: true);

$bot->pinChatMessage($chatId, $messageId, ['disable_notification' => true]);
$bot->unpinChatMessage($chatId, $messageId);
$bot->unpinAllChatMessages($chatId);
```

---

## 9. Downloading files

```php
$file = $bot->getFile($fileId);                 // { file_id, file_path, file_size }
$bytes = $bot->downloadFile($fileId);           // raw binary string
$bot->downloadFileTo($fileId, '/path/save.jpg'); // returns the destination path
```

**Example response (getFile):**

```json
{ "file_id": "AgACAg...", "file_unique_id": "AQAD…", "file_size": 12345, "file_path": "photos/file_1.jpg" }
```

---

## 10. Chat administration

```php
$bot->getChat($chatId);
$bot->getChatMember($chatId, $userId);
$bot->getChatAdministrators($chatId);
$bot->getChatMemberCount($chatId);

$bot->banChatMember($chatId, $userId, ['until_date' => time() + 3600]);
$bot->unbanChatMember($chatId, $userId);
$bot->restrictChatMember($chatId, $userId, ['can_send_messages' => false]);
$bot->promoteChatMember($chatId, $userId, ['can_delete_messages' => true, 'can_restrict_members' => true]);

$bot->setChatTitle($chatId, 'New title');
$bot->setChatDescription($chatId, 'Description');
$bot->createChatInviteLink($chatId, ['member_limit' => 10]);
$bot->exportChatInviteLink($chatId);
$bot->leaveChat($chatId);
```

---

## 11. Bot settings & commands

```php
$bot->setMyCommands([
    ['command' => 'start', 'description' => 'Start the bot'],
    ['command' => 'help', 'description' => 'Get help'],
]);
$bot->getMyCommands();
$bot->deleteMyCommands();

$bot->setMyName('TexHub Bot');
$bot->setMyDescription('I help you automate Telegram.');
$bot->setMyShortDescription('Automation bot');
```

---

## 12. Payments (invoices & Stars)

```php
// Telegram Stars (digital goods): currency "XTR", empty provider token.
$bot->sendInvoice(
    $chatId,
    title: 'Pro plan',
    description: 'One month of Pro',
    payload: 'order-123',
    currency: 'XTR',
    prices: [['label' => 'Pro', 'amount' => 500]], // 500 Stars
);

// Reusable invoice link:
$link = $bot->createInvoiceLink('Pro', 'Monthly', 'order-123', 'XTR', [['label' => 'Pro', 'amount' => 500]]);
echo $link->value();   // https://t.me/$invoice...

// In your webhook, approve the pre-checkout, then handle successful_payment:
$bot->answerPreCheckoutQuery($preCheckoutQueryId, true);
$bot->answerShippingQuery($shippingQueryId, true, ['shipping_options' => [...]]);

// Refund Stars:
$bot->refundStarPayment($userId, $telegramPaymentChargeId);
```

---

## 13. Games

```php
$bot->sendGame($chatId, 'my_game_short_name');
$bot->setGameScore($userId, 100, ['chat_id' => $chatId, 'message_id' => $messageId]);
$bot->getGameHighScores($userId, ['chat_id' => $chatId, 'message_id' => $messageId]);
```

---

## 14. Webhooks

```php
// Register (with a secret token for security):
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'my-webhook-secret',
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);

$bot->getWebhookInfo();
$bot->unsetWebhook();          // alias of deleteWebhook()
$bot->deleteWebhook(dropPendingUpdates: true);
```

**Receiving** — verify the secret header, then parse:

```php
$bot->webhooks()->assertValid($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null);

$update = $bot->webhooks()->parse(file_get_contents('php://input'));
```

**Long polling** (no webhook):

```php
$offset = 0;
while (true) {
    foreach ($bot->getUpdates(['offset' => $offset, 'timeout' => 30]) as $update) {
        $offset = $update->id() + 1;
        // handle $update
    }
}
```

---

## 15. Receiving updates (the `Update` object)

```php
$update = $bot->webhooks()->parse($rawJson);

$update->id();                  // update_id
$update->type();                // "message" | "callback_query" | "business_message" | …
$update->isMessage();
$update->isCallbackQuery();
$update->isCommand();           // text starts with "/"
$update->text();                // message text OR callback data
$update->chatId();
$update->fromId();
$update->from();                // user array
$update->message();             // message array (regular/edited/channel/business)
$update->callbackQuery();
$update->inlineQuery();

// Payments:
$update->preCheckoutQuery();
$update->shippingQuery();
$update->successfulPayment();

// Business:
$update->isBusiness();
$update->businessConnectionId();
```

**A typical webhook handler:**

```php
$update = $bot->webhooks()->parse(file_get_contents('php://input'));

if ($update->isCommand() && $update->text() === '/start') {
    $bot->sendMessage($update->chatId(), 'Welcome! 👋', [
        'reply_markup' => InlineKeyboard::make()->row(Button::callback('Menu', 'menu')),
    ]);
}

if ($update->isCallbackQuery()) {
    $cb = $update->callbackQuery();
    $bot->answerCallbackQuery($cb['id'], ['text' => 'You picked: ' . $cb['data']]);
}

http_response_code(200);
```

**Example incoming update (message):**

```json
{ "update_id": 100, "message": { "message_id": 7,
  "from": { "id": 42, "first_name": "Ali", "username": "ali" },
  "chat": { "id": 42, "type": "private" }, "date": 1717250000, "text": "/start" } }
```

### Everything that can arrive (Update accessors)

```php
$update->messageId();   // incoming message id
$update->text();        // text or callback data
$update->caption();     // media caption
$update->photo();       $update->photoFileId();
$update->video();       $update->document();   $update->audio();
$update->voice();       $update->animation();  $update->sticker();  $update->videoNote();
$update->location();    // ['latitude' => .., 'longitude' => ..]
$update->contact();     // ['phone_number' => .., 'first_name' => ..]
$update->venue();       $update->poll();       $update->dice();
$update->fileId();      // first file_id found on the message
```

### Build a bot the easy way — `UpdateHandler`

The simplest way to build a bot. Extend `UpdateHandler` and write a method
**named after each command** — `start` handles `/start`. The argument is whatever
follows the command (e.g. a referral code from `/start REF123`). Inside, `$this->chat`
is a fluent helper bound to the current chat.

```php
use TexHub\Telegram\Handler\UpdateHandler;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\Button;

class MyBot extends UpdateHandler
{
    // "/start"  →  start();   "/start REF123"  →  start('REF123')
    public function start(string $payload = ''): void
    {
        if ($payload !== '') {
            // Opened via t.me/yourbot?start=REF123 — capture who invited them:
            $this->chat->message("Invited with code: {$payload}")->send();
        }

        $this->chat->message('Welcome! 👋')
            ->keyboard(InlineKeyboard::make()->row(Button::callback('Menu', 'menu')))
            ->send();
    }

    public function help(): void                  { $this->chat->message('Send /start')->send(); }

    public function onText(string $text): void    { $this->chat->message('You said: ' . $text)->send(); }
    public function onPhoto(): void               { $this->chat->message('Nice photo ' . $this->update->photoFileId())->send(); }
    public function onLocation(array $l): void    { $this->chat->message("📍 {$l['latitude']}, {$l['longitude']}")->send(); }
    public function onContact(array $c): void     { $this->chat->message('📱 ' . $c['phone_number'])->send(); }
    public function onCallbackQuery(array $cb): void { $this->answerCallback('OK'); }
}
```

Wire it in your webhook — one line (it verifies the secret, parses and dispatches):

```php
(new MyBot)->handleRequest(
    $bot,
    $request->getContent(),
    $request->header('X-Telegram-Bot-Api-Secret-Token'),
);
```

**Commands** are matched by method name: `/start` → `start()`, `/help` → `help()`
(you may also use `commandStart()` or a generic `onCommand($cmd, $payload)`).

**Events** to override: `onText($text)`, `onPhoto`, `onVideo`, `onDocument`, `onVoice`,
`onAudio`, `onAnimation`, `onSticker`, `onLocation($loc)`, `onContact($c)`,
`onCallbackQuery($cb)`, `onInlineQuery($q)`, `onPreCheckoutQuery($q)`, `onMessage`, `onOther`.

**Inside a handler:** `$this->chat` (fluent), `$this->bot`, `$this->update`, plus shortcuts
`reply()`, `replyPhoto()`, `replyChatAction()`, `answerCallback()`, `chatId()`, `fromId()`.

The fluent `$this->chat` reads naturally:

```php
$this->chat->message('Hello <b>world</b>')->html()->send();
$this->chat->photo('/path/pic.jpg')->caption('Look')->send();
$this->chat->message('Choose:')->keyboard(InlineKeyboard::make()->row(Button::callback('A', 'a')))->send();
$this->chat->action('typing');
```

---

## 16. Telegram Business

```php
// Incoming business update:
if ($update->isBusiness()) {
    $connId = $update->businessConnectionId();
    $bot->asBusiness($connId)->sendMessage($update->chatId(), 'Reply on behalf of the business');
}

// Inspect a connection:
$bot->getBusinessConnection($connId);

// Any send method also accepts business_connection_id directly:
$bot->sendMessage($chatId, 'Hi', ['business_connection_id' => $connId]);
```

---

## 17. Multi-tenant (many bots)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => [
        'support' => ['token' => '111:AAA', 'webhook_secret' => 'sA'],
        'sales'   => ['token' => '222:BBB'],
    ],
]);

$tg->driver('support')->sendMessage($chatId, 'Hi from support');
$tg->driver('sales')->sendMessage($chatId, 'Hi from sales');
$tg->sendMessage($chatId, 'Default bot');                  // forwards to default

// Build a bot from a token stored in your DB at runtime:
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, 'Tenant bot');
```

---

## 18. Any other API method (`call`)

Everything in the Bot API is reachable, even methods without a typed helper:

```php
$bot->call('setChatPhoto', ['chat_id' => $chatId, 'photo' => InputFile::fromPath('/path/logo.jpg')]);
$bot->call('approveChatJoinRequest', ['chat_id' => $chatId, 'user_id' => $userId]);
$bot->call('getUserProfilePhotos', ['user_id' => $userId]);
```

Returns a `Response`; use `->value()`, `->get('dot.path')`, `->toArray()`, `->boolean()`.

---

## 19. Error handling

```php
try {
    $bot->sendMessage($chatId, 'Hello');
} catch (ApiException $e) {
    $e->errorCode;      // e.g. 429, 400, 403
    $e->getMessage();   // Telegram's description
    $e->retryAfter();   // seconds to wait (flood control)
    $e->isRateLimit();
    $e->isUnauthorized();
} catch (TelegramException $e) {
    // transport / invalid-response / config errors
}
```

**Example error response from Telegram:**

```json
{ "ok": false, "error_code": 429, "description": "Too Many Requests: retry after 5",
  "parameters": { "retry_after": 5 } }
```

---

## 20. Laravel

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations   # optional, multi-tenant
php artisan migrate
```

### Artisan commands

Manage bots straight from the terminal:

```bash
# Interactive: paste the token, it validates, offers to auto-generate a webhook
# secret and to register the webhook, then stores the bot in the DB.
php artisan telegram:bot:add

php artisan telegram:bots                       # list all bots (DB + config)
php artisan telegram:webhook:set {bot?}         # URL auto-built from APP_URL; secret auto-generated
php artisan telegram:webhook:unset {bot?} --drop-pending
php artisan telegram:webhook:info {bot?}        # show current webhook info
```

`{bot}` is the bot name or DB id (omit it to use the default config bot).

`telegram:webhook:set` **builds the URL automatically** — `APP_URL` + `telegram.webhook.path`
(default `telegram/webhook`) — so you don't type it. Pass a URL to override per call, or set
`TELEGRAM_WEBHOOK_URL` globally. Relevant `.env`:

```dotenv
APP_URL=https://your-domain.tld
# TELEGRAM_WEBHOOK_PATH=telegram/webhook    # optional (default)
# TELEGRAM_WEBHOOK_URL=https://...          # optional full override
# TELEGRAM_WEBHOOK_APPEND_BOT=true          # append /{bot} for per-bot routing
```

`.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC
TELEGRAM_WEBHOOK_SECRET=my-secret
TELEGRAM_PARSE_MODE=HTML
```

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Hi from Laravel!');       // default bot
Telegram::driver('sales')->sendMessage($chatId, '...');   // named bot
```

DB-driven tenant bot:

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Hello from a tenant bot');
```

### Saving chats (the `TelegramChat` model)

Store every chat/user that interacts with your bot — `chat_id`, name, username,
language, avatar and more:

```php
use TexHub\Telegram\Laravel\Models\TelegramChat;

// In your webhook, remember whoever wrote to the bot:
$chat = TelegramChat::rememberFromUpdate($update, $bot?->id);   // upsert by (bot, chat_id)

// Fetch & store the avatar (profile photo) and resolve a URL:
$chat->refreshAvatar($botClient);
$url = $chat->avatarUrl($botClient);

// Relations:
$bot->chats()->where('is_active', true)->get();   // all chats of a bot
$chat->bot;                                        // the owning bot
```

Webhook controller:

```php
public function webhook(string $bot, \Illuminate\Http\Request $request)
{
    $client = Telegram::driver($bot);
    $client->webhooks()->assertValid($request->header('X-Telegram-Bot-Api-Secret-Token'));

    $update = $client->webhooks()->parse($request->getContent());
    // ...handle

    return response('', 200);
}
```

> Exclude the webhook route from CSRF (`VerifyCsrfToken::$except` / `validateCsrfTokens(except: [...])`).

---

## 21. Testing

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);

$bot->sendMessage(99, 'hi');

// assert what was sent:
$t->last()['params']['text'];   // 'hi'
$t->lastMethod();               // 'sendMessage'
```

---

MIT © TexHub Pro — built by Mahmudi Shodmehr.
