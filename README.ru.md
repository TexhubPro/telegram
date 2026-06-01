# TexHub · Telegram

[English](README.md) · **🌐 Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Полнофункциональный **мультитенантный** SDK для **Telegram Bot API** и **Telegram Business** для любого PHP-фреймворка — сообщения, медиа, клавиатуры, файлы, вебхуки, long polling — с полной поддержкой **Laravel**.

> **Весь Bot API** доступен через `->call()`, плюс типизированные методы и билдеры для частых случаев. Безопасно по умолчанию: проверка secret-токена вебхука и сокрытие токена в ошибках.

Документация: <https://core.telegram.org/bots/api>

---

## ✨ Возможности

- 💬 **Сообщения** — текст, фото, документ, видео, аудио, голос, анимация, стикер, медиагруппа, локация, контакт, опрос, dice
- ⌨️ **Клавиатуры** — текучие билдеры inline и reply + все типы кнопок
- ✏️ **Редактирование / удаление / пересылка / копирование**, chat actions, ответы на callback и inline
- 📂 **Файлы** — загрузка локальных файлов (multipart), `getFile`, скачивание байтов / на диск
- 🔔 **Вебхуки** — set/delete, **проверка secret-токена**, богатый парсер `Update`
- 💼 **Telegram Business** — бизнес-апдейты + отправка от имени подключения
- 🏢 **Multi-tenant** — много ботов по имени или из токена в БД на лету
- 🧩 **Полное покрытие** — `->call('anyMethod', [...])` для всего остального
- 🧪 Полностью покрыт тестами, подменяемый HTTP-транспорт

---

## 📦 Установка

```bash
composer require texhub/telegram
```

Требования: **PHP ≥ 8.2** с `curl`, `json`, `hash`.

---

## 🚀 Быстрый старт

```php
use TexHub\Telegram\Telegram;

$bot = Telegram::bot('123456:ABC-TOKEN');

$bot->sendMessage($chatId, '<b>Привет!</b>', ['parse_mode' => 'HTML']);
$bot->getMe();
```

### Клавиатуры

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

### Медиа и файлы

```php
use TexHub\Telegram\InputFile;

$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'Фото']);
$bot->sendPhoto($chatId, InputFile::fromPath('/path/local.jpg'));     // multipart-загрузка
$bot->sendDocument($chatId, InputFile::fromPath('/path/invoice.pdf'));

$bytes = $bot->downloadFile($fileId);
$bot->downloadFileTo($fileId, storage_path('app/file.jpg'));
```

### Всё остальное

```php
$bot->call('setMyCommands', ['commands' => [['command' => 'start', 'description' => 'Start']]]);
$bot->call('banChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
```

---

## 🔔 Вебхуки

**Регистрация** (с secret-токеном для безопасности):

```php
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'your-secret',                 // или задайте в конфиге
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);
```

**Приём** — проверьте secret-заголовок, затем разберите апдейт:

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

Хелперы `$update`: `type()`, `message()`, `callbackQuery()`, `inlineQuery()`,
`text()`, `chatId()`, `from()`, `fromId()`, `isCommand()`, `isBusiness()`,
`businessConnectionId()`.

---

## 💼 Telegram Business

```php
// Входящие бизнес-апдейты:
if ($update->isBusiness()) {
    $connId = $update->businessConnectionId();
    $bot->asBusiness($connId)->sendMessage($update->chatId(), 'Ответ от бизнес-аккаунта');
}

$bot->getBusinessConnection($connId);
```

Любой метод отправки также принимает `business_connection_id` прямо в опциях.

---

## 🏢 Multi-tenant (много ботов)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => [
        'support' => ['token' => '111:AAA', 'webhook_secret' => '...'],
        'sales'   => ['token' => '222:BBB'],
    ],
]);

$tg->driver('support')->sendMessage($chatId, 'Привет из support');
$tg->driver('sales')->sendMessage($chatId, 'Привет из sales');
$tg->sendMessage($chatId, 'Бот по умолчанию');      // проксируется на default

// Бот из токена, загруженного из БД на лету:
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, '...');
```

В Laravel опциональная таблица `telegram_bots` + модель `TelegramBot` делают это «из коробки» (см. ниже).

---

## ⚙️ Обработка ошибок

```php
use TexHub\Telegram\Exceptions\ApiException;

try {
    $bot->sendMessage($chatId, 'x');
} catch (ApiException $e) {
    $e->errorCode;        // напр. 429
    $e->getMessage();     // описание
    $e->retryAfter();     // секунды (flood control)
    $e->isRateLimit();
}
```

---

## <a name="laravel"></a>🧩 Laravel

Регистрируется автоматически. Опубликуйте конфиг (и опц. миграцию для БД-ботов):

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations   # опционально, для multi-tenant
```

`.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC
TELEGRAM_WEBHOOK_SECRET=your-secret
TELEGRAM_PARSE_MODE=HTML
```

Фасад:

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Привет из Laravel!');       // бот по умолчанию
Telegram::driver('sales')->sendMessage($chatId, '...');     // именованный бот
```

### Мультитенант на БД

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Привет от бота арендатора');
```

### Контроллер вебхука

```php
public function webhook(string $bot, Request $request)
{
    $client = Telegram::driver($bot);                       // или резолв по токену из БД
    $client->webhooks()->assertValid($request->header('X-Telegram-Bot-Api-Secret-Token'));

    $update = $client->webhooks()->parse($request->getContent());
    // ...обработка $update
    return response('', 200);
}
```

> Исключите маршрут вебхука из CSRF (`VerifyCsrfToken::$except`).

---

## 🧪 Тестирование

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);
$bot->sendMessage(99, 'hi'); // проверяйте $t->last()
```

```bash
composer install && composer test
```

---

## 📚 Архитектура

```
src/
├── Telegram.php             # менеджер многих ботов (driver/botFromToken)
├── Bot.php                  # клиент — call() + типизированные методы + asBusiness()
├── Config.php · Response.php · Update.php · InputFile.php
├── Enums/                   # ParseMode, ChatAction
├── Keyboard/                # InlineKeyboard, ReplyKeyboard, Button
├── Http/                    # Transport, CurlTransport (JSON+multipart), ApiClient
├── Webhook/                 # WebhookHandler (проверка secret + разбор)
├── Exceptions/              # ApiException (retryAfter), …
└── Laravel/                 # ServiceProvider, Facade, Models/TelegramBot, миграция
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
