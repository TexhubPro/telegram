# TexHub · Telegram

**English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A full-featured, **multi-tenant** Telegram **Bot API** & **Telegram Business** SDK for any PHP framework — built to be **beginner-friendly**: write a method named after each command, reply with a fluent `$this->chat`, and manage bots from the terminal.

> The **entire Bot API** is reachable via `->call()`, with typed helpers and builders for the common cases. Secure by default: webhook secret-token verification and token scrubbing in errors.

> **[Full method reference & cookbook →](docs/REFERENCE.md)** — every method with copy-paste examples and example responses.

Reference: <https://core.telegram.org/bots/api>

---

## Features

- **Easy bot builder** — extend `UpdateHandler`, write `public function start()` for `/start`
- **Fluent replies** — `$this->chat->message('Hi')->html()->keyboard(...)->send()`
- **Keyboards** — inline & reply builders, every button type (web app, **copy text**, login, …)
- **Messages & media** — text, photo, video, audio, voice, document, sticker, location, contact, poll, dice
- **Edit / delete / forward / copy**, reactions, pin, chat actions
- **Files** — upload local files, `getFile`, download bytes/to disk
- **Payments** (invoices & Telegram Stars) and **Games**
- **Webhooks** — secret-token verification + a rich `Update` parser for everything that arrives
- **Telegram Business** — business updates + send on behalf of a connection
- **Multi-tenant** — many bots; store them (and every chat) in your DB
- **Artisan commands** — add bots, set/unset webhooks, list bots
- **Full coverage** — `->call('anyMethod', [...])` for everything else; fully unit-tested

---

## Installation

```bash
composer require texhub/telegram
```

Requirements: **PHP ≥ 8.2** with `curl`, `json`, `hash`.

---

## Build a bot (the easy way)

Extend `UpdateHandler` and write a method **named after each command**. The argument is
whatever follows it (e.g. a referral code from `/start REF123`). Reply with the fluent
`$this->chat`:

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
            $this->chat->message("Invited with code: {$payload}")->send();   // who invited
        }

        $this->chat->message('Welcome! 👋')
            ->keyboard(InlineKeyboard::make()->row(Button::callback('Menu', 'menu')))
            ->send();
    }

    public function help(): void                  { $this->chat->message('Send /start')->send(); }
    public function onText(string $text): void    { $this->chat->message('You said: ' . $text)->send(); }
    public function onPhoto(): void               { $this->chat->message('Nice photo!')->send(); }
    public function onContact(array $c): void      { $this->chat->message('📱 ' . $c['phone_number'])->send(); }
    public function onLocation(array $l): void     { $this->chat->message("📍 {$l['latitude']}, {$l['longitude']}")->send(); }
    public function onCallbackQuery(array $cb): void { $this->answerCallback('OK'); }
}
```

Wire it in your webhook — one line (it verifies the secret, parses and dispatches):

```php
(new MyBot)->handleRequest($bot, $request->getContent(), $request->header('X-Telegram-Bot-Api-Secret-Token'));
```

Override `onText`, `onPhoto`, `onVideo`, `onDocument`, `onVoice`, `onAudio`, `onAnimation`,
`onSticker`, `onLocation`, `onContact`, `onCallbackQuery`, `onInlineQuery`, `onPreCheckoutQuery`,
`onMessage`, `onOther` — and any `commandName()`.

---

## Sending directly (without a handler)

```php
use TexHub\Telegram\Telegram;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\Button;
use TexHub\Telegram\InputFile;

$bot = Telegram::bot('123456:ABC-TOKEN');

// Fluent:
$bot->chat($chatId)->message('Hello <b>world</b>')->html()->send();
$bot->chat($chatId)->photo(InputFile::fromPath('/path/pic.jpg'))->caption('Look')->send();
$bot->chat($chatId)
    ->message('Choose:')
    ->keyboard(InlineKeyboard::make()->row(
        Button::callback('✅ Yes', 'yes'),
        Button::copyText('📋 Copy code', 'PROMO-2026'),
        Button::webApp('🚀 Open app', 'https://app.texhub.pro'),
    ))
    ->send();

// Classic:
$bot->sendMessage($chatId, 'Hi', ['parse_mode' => 'HTML']);
$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'Photo']);
$bot->downloadFileTo($fileId, '/path/save.jpg');

// Anything in the Bot API:
$bot->call('banChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
```

See the **[full reference](docs/REFERENCE.md)** for every method (payments, games, chat admin, …).

---

## Webhooks

```php
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'your-secret',
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);
$bot->unsetWebhook();
$bot->getWebhookInfo();
```

The incoming `Update` exposes **everything**: `text()`, `photo()/photoFileId()`, `video()`,
`document()`, `voice()`, `audio()`, `animation()`, `sticker()`, `location()`, `contact()`,
`venue()`, `poll()`, `dice()`, `caption()`, `chatId()`, `fromId()`, `messageId()`, `fileId()`,
`callbackQuery()`, `isCommand()`, `isBusiness()`, `businessConnectionId()`.

---

## Telegram Business

```php
if ($update->isBusiness()) {
    $bot->asBusiness($update->businessConnectionId())
        ->sendMessage($update->chatId(), 'Reply on behalf of the business');
}
```

---

## Multi-tenant (many bots)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => ['support' => ['token' => '111:AAA'], 'sales' => ['token' => '222:BBB']],
]);

$tg->driver('sales')->sendMessage($chatId, 'Hi from sales');
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, '...'); // from DB at runtime
```

---

## <a name="laravel"></a> Laravel

Auto-discovered. Publish config (+ optional migrations for storing bots & chats):

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations
php artisan migrate
```

### Artisan commands

```bash
php artisan telegram:bot:add        # interactive: token → auto-secret → set webhook → save to DB
php artisan telegram:bots           # list all bots
php artisan telegram:webhook:set {bot?} {url?}
php artisan telegram:webhook:unset {bot?} --drop-pending
php artisan telegram:webhook:info {bot?}
```

### Facade

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Hi from Laravel!');         // default bot
Telegram::driver('sales')->chat($chatId)->message('…')->send();
```

### Models (store bots & chats)

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;
use TexHub\Telegram\Laravel\Models\TelegramChat;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Hello from a tenant bot');

// Remember everyone who writes to the bot (chat_id, name, username, language, avatar…):
$chat = TelegramChat::rememberFromUpdate($update, $record->id);
$chat->refreshAvatar($record->client());
$record->chats()->where('is_active', true)->get();
```

### Webhook controller

```php
public function webhook(string $bot, \Illuminate\Http\Request $request)
{
    (new \App\Telegram\MyBot)->handleRequest(
        \TexHub\Telegram\Laravel\Telegram::driver($bot),
        $request->getContent(),
        $request->header('X-Telegram-Bot-Api-Secret-Token'),
    );

    return response('', 200);
}
```

> Exclude the webhook route from CSRF (`validateCsrfTokens(except: ['telegram/webhook'])`).

---

## Testing

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);
$bot->chat(99)->message('hi')->send(); // assert on $t->last()
```

```bash
composer install && composer test
```

---

## Architecture

```
src/
├── Telegram.php             # multi-bot manager (driver/botFromToken)
├── Bot.php                  # client — call() + typed methods + chat()/asBusiness()
├── Handler/UpdateHandler.php# extend-me dispatcher (command*/on* methods)
├── Messaging/               # ChatContext + PendingMessage (fluent builder)
├── Update.php               # rich accessors for everything that arrives
├── Keyboard/                # InlineKeyboard, ReplyKeyboard, Button
├── Enums/ · Http/ · Webhook/ · Exceptions/
└── Laravel/                 # ServiceProvider, Facade, Commands/, Models (Bot + Chat), migrations
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
