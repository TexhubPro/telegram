# TexHub · Telegram

[English](README.md) · **Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Полнофункциональный **мультитенантный** SDK для **Telegram Bot API** и **Telegram Business** для любого PHP-фреймворка — сделан **дружелюбным к новичкам**: пишешь метод с именем команды, отвечаешь текучим `$this->chat`, управляешь ботами из терминала.

> **Весь Bot API** доступен через `->call()`, плюс типизированные методы и билдеры для частых случаев. Безопасно по умолчанию: проверка secret-токена вебхука и сокрытие токена в ошибках.

> **[Полный справочник методов и примеров →](docs/REFERENCE.ru.md)** — каждый метод с готовыми примерами и примерами ответов.

Документация: <https://core.telegram.org/bots/api>

---

## Возможности

- **Простой конструктор бота** — расширь `UpdateHandler`, напиши `public function start()` для `/start`
- **Текучие ответы** — `$this->chat->message('Привет')->html()->keyboard(...)->send()`
- **Клавиатуры** — билдеры inline и reply, все типы кнопок (web app, **копирование текста**, login, …)
- **Сообщения и медиа** — текст, фото, видео, аудио, голос, документ, стикер, локация, контакт, опрос, dice
- **Редактирование / удаление / пересылка / копирование**, реакции, закрепление, chat actions
- **Файлы** — загрузка локальных файлов, `getFile`, скачивание байтов/на диск
- **Платежи** (инвойсы и Telegram Stars) и **Игры**
- **Вебхуки** — проверка secret-токена + богатый разбор `Update` всего, что приходит
- **Telegram Business** — бизнес-апдейты + отправка от имени подключения
- **Multi-tenant** — много ботов; храни их (и все чаты) в своей БД
- **Artisan-команды** — добавление ботов, set/unset вебхуков, список
- **Полное покрытие** — `->call('любойМетод', [...])`; полностью покрыт тестами

---

## Установка

```bash
composer require texhub/telegram
```

Требования: **PHP ≥ 8.2** с `curl`, `json`, `hash`.

---

## Бот по-простому

Расширь `UpdateHandler` и напиши метод **с именем команды**. Аргумент — то, что идёт после
команды (например реферальный код из `/start REF123`). Отвечай текучим `$this->chat`:

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
            $this->chat->message("Код приглашения: {$payload}")->send();   // кто пригласил
        }

        $this->chat->message('Добро пожаловать! 👋')
            ->keyboard(InlineKeyboard::make()->row(Button::callback('Меню', 'menu')))
            ->send();
    }

    public function help(): void                  { $this->chat->message('Напишите /start')->send(); }
    public function onText(string $text): void    { $this->chat->message('Вы написали: ' . $text)->send(); }
    public function onPhoto(): void               { $this->chat->message('Хорошее фото!')->send(); }
    public function onContact(array $c): void      { $this->chat->message('📱 ' . $c['phone_number'])->send(); }
    public function onLocation(array $l): void     { $this->chat->message("📍 {$l['latitude']}, {$l['longitude']}")->send(); }
    public function onCallbackQuery(array $cb): void { $this->answerCallback('OK'); }
}
```

Подключение в вебхуке — одна строка (сам проверяет secret, парсит и маршрутизирует):

```php
(new MyBot)->handleRequest($bot, $request->getContent(), $request->header('X-Telegram-Bot-Api-Secret-Token'));
```

Переопределяй `onText`, `onPhoto`, `onVideo`, `onDocument`, `onVoice`, `onAudio`, `onAnimation`,
`onSticker`, `onLocation`, `onContact`, `onCallbackQuery`, `onInlineQuery`, `onPreCheckoutQuery`,
`onMessage`, `onOther` — и любой `commandName()`.

---

## Отправка напрямую (без хендлера)

```php
use TexHub\Telegram\Telegram;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Keyboard\Button;
use TexHub\Telegram\InputFile;

$bot = Telegram::bot('123456:ABC-TOKEN');

// Текуче:
$bot->chat($chatId)->message('Привет <b>мир</b>')->html()->send();
$bot->chat($chatId)->photo(InputFile::fromPath('/path/pic.jpg'))->caption('Смотри')->send();
$bot->chat($chatId)
    ->message('Выбор:')
    ->keyboard(InlineKeyboard::make()->row(
        Button::callback('✅ Да', 'yes'),
        Button::copyText('📋 Скопировать код', 'PROMO-2026'),
        Button::webApp('🚀 Открыть приложение', 'https://app.texhub.pro'),
    ))
    ->send();

// Классически:
$bot->sendMessage($chatId, 'Привет', ['parse_mode' => 'HTML']);
$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'Фото']);
$bot->downloadFileTo($fileId, '/path/save.jpg');

// Что угодно из Bot API:
$bot->call('banChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
```

Все методы (платежи, игры, админ чата, …) — в **[полном справочнике](docs/REFERENCE.ru.md)**.

---

## Вебхуки

```php
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'your-secret',
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);
$bot->unsetWebhook();
$bot->getWebhookInfo();
```

Входящий `Update` отдаёт **всё**: `text()`, `photo()/photoFileId()`, `video()`,
`document()`, `voice()`, `audio()`, `animation()`, `sticker()`, `location()`, `contact()`,
`venue()`, `poll()`, `dice()`, `caption()`, `chatId()`, `fromId()`, `messageId()`, `fileId()`,
`callbackQuery()`, `isCommand()`, `isBusiness()`, `businessConnectionId()`.

---

## Telegram Business

```php
if ($update->isBusiness()) {
    $bot->asBusiness($update->businessConnectionId())
        ->sendMessage($update->chatId(), 'Ответ от имени бизнес-аккаунта');
}
```

---

## Multi-tenant (много ботов)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => ['support' => ['token' => '111:AAA'], 'sales' => ['token' => '222:BBB']],
]);

$tg->driver('sales')->sendMessage($chatId, 'Привет из sales');
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, '...'); // из БД на лету
```

---

## <a name="laravel"></a> Laravel

Регистрируется автоматически. Опубликуй конфиг (+ опц. миграции для хранения ботов и чатов):

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations
php artisan migrate
```

### Artisan-команды

```bash
php artisan telegram:bot:add        # интерактивно: токен → авто-секрет → вебхук → сохранить в БД
php artisan telegram:bots           # список всех ботов
php artisan telegram:webhook:set {bot?} {url?}
php artisan telegram:webhook:unset {bot?} --drop-pending
php artisan telegram:webhook:info {bot?}
```

### Фасад

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Привет из Laravel!');       // бот по умолчанию
Telegram::driver('sales')->chat($chatId)->message('…')->send();
```

### Модели (хранение ботов и чатов)

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;
use TexHub\Telegram\Laravel\Models\TelegramChat;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Привет от бота арендатора');

// Запоминаем всех, кто пишет боту (chat_id, имя, username, язык, аватар…):
$chat = TelegramChat::rememberFromUpdate($update, $record->id);
$chat->refreshAvatar($record->client());
$record->chats()->where('is_active', true)->get();
```

### Контроллер вебхука

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

> Исключи маршрут вебхука из CSRF (`validateCsrfTokens(except: ['telegram/webhook'])`).

---

## Тестирование

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);
$bot->chat(99)->message('привет')->send(); // проверяйте $t->last()
```

```bash
composer install && composer test
```

---

## Архитектура

```
src/
├── Telegram.php             # менеджер многих ботов (driver/botFromToken)
├── Bot.php                  # клиент — call() + типизированные методы + chat()/asBusiness()
├── Handler/UpdateHandler.php# наследуемый диспетчер (методы command*/on*)
├── Messaging/               # ChatContext + PendingMessage (текучий билдер)
├── Update.php               # богатые аксессоры всего, что приходит
├── Keyboard/                # InlineKeyboard, ReplyKeyboard, Button
├── Enums/ · Http/ · Webhook/ · Exceptions/
└── Laravel/                 # ServiceProvider, Facade, Commands/, модели (Bot + Chat), миграции
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
