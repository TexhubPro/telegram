# TexHub · Telegram

**🌐 English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A full-featured, **multi-tenant** Telegram **Bot API** & **Telegram Business** SDK for any PHP framework — messages, media, keyboards, files, webhooks, long polling — with first-class **Laravel** support.

> The **entire Bot API** is reachable via `->call()`, with typed helpers and builders for the common cases. Secure by default: webhook secret-token verification and token scrubbing in errors.

> 📘 **[Full method reference & cookbook →](docs/REFERENCE.md)** — every method with copy-paste examples and example responses.

Reference: <https://core.telegram.org/bots/api>

---

## ✨ Features

- 💬 **Messages** — text, photo, document, video, audio, voice, animation, sticker, media group, location, contact, poll, dice
- ⌨️ **Keyboards** — fluent inline & reply keyboard builders + every button type
- ✏️ **Edit / delete / forward / copy**, chat actions, callback & inline answers
- 📂 **Files** — upload local files (multipart), `getFile`, download bytes/to disk
- 🔔 **Webhooks** — set/delete, **secret-token verification**, rich `Update` parser
- 💼 **Telegram Business** — business updates + send on behalf of a connection
- 🏢 **Multi-tenant** — many bots by name or built from a DB token at runtime
- 🧩 **Full coverage** — `->call('anyMethod', [...])` for everything else
- 🧪 Fully unit-tested, pluggable HTTP transport

---

## 📦 Installation

```bash
composer require texhub/telegram
```

Requirements: **PHP ≥ 8.2** with `curl`, `json`, `hash`.

---

## 🚀 Quick start

```php
use TexHub\Telegram\Telegram;

$bot = Telegram::bot('123456:ABC-TOKEN');

$bot->sendMessage($chatId, '<b>Привет!</b>', ['parse_mode' => 'HTML']);
$bot->getMe();
```

### Keyboards

```php
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\ReplyKeyboard;
use TexHub\Telegram\Keyboard\Button;

$bot->sendMessage($chatId, 'Подтвердите заказ:', [
    'reply_markup' => InlineKeyboard::make()
        ->row(Button::callback('✅ Да', 'confirm'), Button::callback('❌ Нет', 'cancel'))
        ->row(Button::url('Открыть сайт', 'https://texhub.pro')),
]);

$bot->sendMessage($chatId, 'Поделитесь контактом:', [
    'reply_markup' => ReplyKeyboard::make()
        ->row(Button::requestContact('📱 Контакт'), Button::requestLocation('📍 Локация'))
        ->oneTime()->resize(),
]);
```

### Media & files

```php
use TexHub\Telegram\InputFile;

$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'Фото']);
$bot->sendPhoto($chatId, InputFile::fromPath('/path/local.jpg'));     // multipart upload
$bot->sendDocument($chatId, InputFile::fromPath('/path/invoice.pdf'));

$bytes = $bot->downloadFile($fileId);
$bot->downloadFileTo($fileId, storage_path('app/file.jpg'));
```

### Anything else

```php
$bot->call('setMyCommands', ['commands' => [['command' => 'start', 'description' => 'Start']]]);
$bot->call('banChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
```

---

## 🔔 Webhooks

**Register** (with a secret token for security):

```php
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'your-secret',                 // or set it in config
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);
```

**Receive** — verify the secret header, then parse the update:

```php
$bot->webhooks()->assertValid($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null);

$update = $bot->webhooks()->parse(file_get_contents('php://input'));

if ($update->isCommand() && $update->text() === '/start') {
    $bot->sendMessage($update->chatId(), 'Добро пожаловать!');
}

if ($update->isCallbackQuery()) {
    $bot->answerCallbackQuery($update->callbackQuery()['id'], ['text' => 'Готово']);
}
```

`$update` helpers: `type()`, `message()`, `callbackQuery()`, `inlineQuery()`,
`text()`, `chatId()`, `from()`, `fromId()`, `isCommand()`, `isBusiness()`,
`businessConnectionId()`.

---

## 💼 Telegram Business

```php
// Incoming business updates:
if ($update->isBusiness()) {
    $connId = $update->businessConnectionId();
    $bot->asBusiness($connId)->sendMessage($update->chatId(), 'Reply from the business account');
}

$bot->getBusinessConnection($connId);
```

Every send method also accepts `business_connection_id` directly in its options.

---

## 🏢 Multi-tenant (many bots)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => [
        'support' => ['token' => '111:AAA', 'webhook_secret' => '...'],
        'sales'   => ['token' => '222:BBB'],
    ],
]);

$tg->driver('support')->sendMessage($chatId, 'Hi from support');
$tg->driver('sales')->sendMessage($chatId, 'Hi from sales');
$tg->sendMessage($chatId, 'Default bot');           // forwards to the default

// Build a bot from a token loaded from the database at runtime:
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, '...');
```

In Laravel, an optional `telegram_bots` table + `TelegramBot` model make this turnkey (see below).

---

## ⚙️ Error handling

```php
use TexHub\Telegram\Exceptions\ApiException;

try {
    $bot->sendMessage($chatId, 'x');
} catch (ApiException $e) {
    $e->errorCode;        // e.g. 429
    $e->getMessage();     // description
    $e->retryAfter();     // seconds (flood control)
    $e->isRateLimit();
}
```

---

## <a name="laravel"></a>🧩 Laravel

Auto-discovered. Publish config (and the optional migration for DB-driven bots):

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations   # optional, for multi-tenant
```

`.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC
TELEGRAM_WEBHOOK_SECRET=your-secret
TELEGRAM_PARSE_MODE=HTML
```

Facade:

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Hi from Laravel!');         // default bot
Telegram::driver('sales')->sendMessage($chatId, '...');     // named bot
```

### DB-driven multi-tenant

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Hello from a tenant bot');
```

### Webhook controller

```php
public function webhook(string $bot, Request $request)
{
    $client = Telegram::driver($bot);                       // or resolve by token from DB
    $client->webhooks()->assertValid($request->header('X-Telegram-Bot-Api-Secret-Token'));

    $update = $client->webhooks()->parse($request->getContent());
    // ...handle $update
    return response('', 200);
}
```

> Exclude the webhook route from CSRF (`VerifyCsrfToken::$except`).

---

## 🧪 Testing

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);
$bot->sendMessage(99, 'hi'); // assert on $t->last()
```

```bash
composer install && composer test
```

---

## 📚 Architecture

```
src/
├── Telegram.php             # multi-bot manager (driver/botFromToken)
├── Bot.php                  # the client — call() + typed methods + asBusiness()
├── Config.php · Response.php · Update.php · InputFile.php
├── Enums/                   # ParseMode, ChatAction
├── Keyboard/                # InlineKeyboard, ReplyKeyboard, Button
├── Http/                    # Transport, CurlTransport (JSON+multipart), ApiClient
├── Webhook/                 # WebhookHandler (secret verify + parse)
├── Exceptions/              # ApiException (retryAfter), …
└── Laravel/                 # ServiceProvider, Facade, Models/TelegramBot, migration
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
