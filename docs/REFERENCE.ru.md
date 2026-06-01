# TexHub · Telegram — Полный справочник методов

[English version](REFERENCE.md) · [← Назад к README](../README.ru.md)

Готовый к копированию сборник **всех** методов `texhub/telegram`: от установки до
отправки, приёма, вебхуков, платежей, игр и Telegram Business — каждый с примером
вызова и примером ответа.

> Каждый сниппет готов к вставке. Импорты ниже покрывают все примеры на странице.

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

## Содержание

1. [Установка](#1-установка)
2. [Создание / подключение бота](#2-создание--подключение-бота)
3. [getMe](#3-getme)
4. [Отправка сообщений](#4-отправка-сообщений)
5. [Медиа и файлы](#5-медиа-и-файлы)
6. [Клавиатуры и кнопки](#6-клавиатуры-и-кнопки)
7. [Редактирование и удаление](#7-редактирование-и-удаление)
8. [Реакции, закрепление](#8-реакции-закрепление)
9. [Скачивание файлов](#9-скачивание-файлов)
10. [Администрирование чата](#10-администрирование-чата)
11. [Настройки и команды бота](#11-настройки-и-команды-бота)
12. [Платежи (инвойсы и Stars)](#12-платежи-инвойсы-и-stars)
13. [Игры](#13-игры)
14. [Вебхуки](#14-вебхуки)
15. [Приём апдейтов (объект Update)](#15-приём-апдейтов-объект-update)
16. [Telegram Business](#16-telegram-business)
17. [Мультитенант (много ботов)](#17-мультитенант-много-ботов)
18. [Любой другой метод API (call)](#18-любой-другой-метод-api-call)
19. [Обработка ошибок](#19-обработка-ошибок)
20. [Laravel](#20-laravel)
21. [Тестирование](#21-тестирование)

---

## 1. Установка

```bash
composer require texhub/telegram
```

Требования: PHP ≥ 8.2 с `curl`, `json`, `hash`.

---

## 2. Создание / подключение бота

```php
// Проще всего — один бот по токену (от @BotFather):
$bot = Telegram::bot('123456:ABC-YourBotToken');

// С опциями:
$bot = new Bot(new Config(
    token: '123456:ABC-YourBotToken',
    webhookSecret: 'my-webhook-secret',   // опционально (рекомендуется)
    defaultParseMode: 'HTML',             // опц. формат по умолчанию для текста
    timeout: 30,
));
```

---

## 3. getMe

```php
$me = $bot->getMe();
echo $me->get('username');   // @username вашего бота
```

**Пример ответа** (`$me->toArray()`):

```json
{ "id": 123456, "is_bot": true, "first_name": "My Bot", "username": "my_bot",
  "can_join_groups": true, "can_read_all_group_messages": false, "supports_inline_queries": false }
```

---

## 4. Отправка сообщений

```php
$msg = $bot->sendMessage($chatId, 'Привет, <b>мир</b>!', ['parse_mode' => 'HTML']);

echo $msg->messageId();   // 42
echo $msg->chatId();      // 99999
```

**Пример ответа:**

```json
{ "message_id": 42, "from": { "id": 123456, "is_bot": true, "username": "my_bot" },
  "chat": { "id": 99999, "type": "private" }, "date": 1717250000, "text": "Привет, мир!" }
```

Частые опции: `reply_markup`, `reply_parameters`, `disable_notification`,
`protect_content`, `message_thread_id`, `link_preview_options`, `entities`, `business_connection_id`.

```php
// Ответ на сообщение, без звука, без превью ссылки:
$bot->sendMessage($chatId, 'Ответ', [
    'reply_parameters' => ['message_id' => 42],
    'disable_notification' => true,
    'link_preview_options' => ['is_disabled' => true],
]);
```

---

## 5. Медиа и файлы

Любое медиа можно передать как **file_id**, **публичный URL** или локальный **`InputFile`** (multipart-загрузка).

```php
$bot->sendPhoto($chatId, 'https://example.com/pic.jpg', ['caption' => 'Фото']);
$bot->sendPhoto($chatId, InputFile::fromPath('/path/local.jpg'), ['caption' => 'Локальная загрузка']);
$bot->sendDocument($chatId, InputFile::fromPath('/path/invoice.pdf'));
$bot->sendVideo($chatId, 'https://example.com/clip.mp4', ['caption' => 'Клип']);
$bot->sendAudio($chatId, InputFile::fromPath('/path/song.mp3'));
$bot->sendVoice($chatId, InputFile::fromPath('/path/voice.ogg'));
$bot->sendAnimation($chatId, 'https://example.com/anim.gif');
$bot->sendVideoNote($chatId, InputFile::fromPath('/path/note.mp4'));
$bot->sendSticker($chatId, 'CAACAgIAAxkBA...'); // file_id стикера

// Альбом / медиагруппа:
$bot->sendMediaGroup($chatId, [
    ['type' => 'photo', 'media' => 'https://example.com/1.jpg', 'caption' => 'Один'],
    ['type' => 'photo', 'media' => 'https://example.com/2.jpg'],
]);

// Локация, venue, контакт, опрос, dice:
$bot->sendLocation($chatId, 38.5598, 68.7870);
$bot->sendVenue($chatId, 38.5598, 68.7870, 'Офис', 'Душанбе, пр. Рудаки');
$bot->sendContact($chatId, '992900123456', 'Ali');
$bot->sendPoll($chatId, 'Что любите?', ['PHP', 'JS', 'Go']);
$bot->sendDice($chatId, ['emoji' => '🎲']);

// «печатает…» и другие действия:
$bot->sendChatAction($chatId, ChatAction::Typing);
$bot->sendChatAction($chatId, ChatAction::UploadPhoto);
```

**Пример ответа (sendPhoto):**

```json
{ "message_id": 43, "chat": { "id": 99999, "type": "private" }, "date": 1717250100,
  "photo": [ { "file_id": "AgACAg...", "width": 90, "height": 90 },
             { "file_id": "AgACAg...", "width": 1280, "height": 1280 } ], "caption": "Фото" }
```

### Подпись, альбомы, голосовые и chat actions

```php
// Фото С подписью (это «текст» медиа):
$bot->chat($chatId)->photo('https://x/p.jpg')->caption('Смотри')->send();
$bot->sendPhoto($chatId, 'https://x/p.jpg', ['caption' => 'Смотри']);

// Альбом: несколько фото, подпись на первом:
$bot->chat($chatId)->photos(['https://x/1.jpg', 'https://x/2.jpg', 'https://x/3.jpg'], 'Мой альбом');
// или смешанная медиагруппа:
$bot->chat($chatId)->mediaGroup([
    ['type' => 'photo', 'media' => 'https://x/1.jpg', 'caption' => 'Первое'],
    ['type' => 'video', 'media' => 'https://x/clip.mp4'],
]);

// Голосовые — отправка и приём:
$bot->chat($chatId)->voice(InputFile::fromPath('/path/voice.ogg'))->send();
// входящее: $update->voice()  → ['file_id' => ..., 'duration' => ...];  $update->fileId()

// Chat actions («печатает…», «загружает фото…») — вызвал, и клиент видит индикатор.
// Проще всего — именованные методы на чате:
$bot->chat($chatId)->typing();             // показывает «печатает…»
$bot->chat($chatId)->uploadingPhoto();     // «отправляет фото…»
$bot->chat($chatId)->uploadingVideo();
$bot->chat($chatId)->recordingVoice();
$bot->chat($chatId)->uploadingDocument();
$bot->chat($chatId)->choosingSticker();
$bot->chat($chatId)->findingLocation();

// Или явно:
$bot->sendChatAction($chatId, ChatAction::Typing);
$bot->chat($chatId)->action('upload_voice');
// внутри хендлера: $this->chat->typing();
```

> Действие держится ~5 секунд (или до отправки сообщения). Вызови снова, чтобы продлить.

> Сообщение Telegram содержит **либо** `text` (текстовое), **либо** `caption` (медиа).
> Поэтому для входящего фото/видео читай `$update->caption()`, для текста — `$update->text()`.

---

## 6. Клавиатуры и кнопки

### Inline-клавиатура

```php
$bot->sendMessage($chatId, 'Выберите вариант:', [
    'reply_markup' => InlineKeyboard::make()
        ->row(Button::callback('✅ Да', 'yes'), Button::callback('❌ Нет', 'no'))
        ->row(Button::url('🌐 Сайт', 'https://texhub.pro'))
        ->row(Button::webApp('🚀 Открыть приложение', 'https://app.texhub.pro'))   // Mini App
        ->row(Button::copyText('📋 Скопировать код', 'PROMO-2026'))                // копирование
        ->row(Button::switchInline('Поделиться', 'query'))
        ->row(Button::loginUrl('🔐 Войти', 'https://texhub.pro/tg-login')),
]);
```

Все inline-кнопки: `callback`, `url`, `webApp`, `copyText`, `loginUrl`,
`switchInline`, `switchInlineCurrent`, `pay`, `callbackGame`.

### Reply-клавиатура

```php
$bot->sendMessage($chatId, 'Поделитесь данными:', [
    'reply_markup' => ReplyKeyboard::make()
        ->row(Button::requestContact('📱 Контакт'), Button::requestLocation('📍 Локация'))
        ->row(Button::requestPoll('📊 Создать опрос'))
        ->row(Button::requestUsers('👥 Выбрать пользователей', requestId: 1))
        ->row(Button::requestChat('💬 Выбрать чат', requestId: 2))
        ->resize()->oneTime()->persistent()->placeholder('Выберите вариант…'),
]);

// Убрать клавиатуру:
$bot->sendMessage($chatId, 'Готово', ['reply_markup' => ReplyKeyboard::remove()]);

// Принудительный ответ:
$bot->sendMessage($chatId, 'Ваше имя?', ['reply_markup' => ReplyKeyboard::forceReply('Введите здесь…')]);
```

---

## 7. Редактирование и удаление

```php
$bot->editMessageText($chatId, $messageId, 'Новый текст', ['parse_mode' => 'HTML']);
$bot->editMessageCaption($chatId, $messageId, 'Новая подпись');
$bot->editMessageMedia($chatId, $messageId, ['type' => 'photo', 'media' => 'https://example.com/new.jpg']);
$bot->editMessageReplyMarkup($chatId, $messageId, InlineKeyboard::make()->row(Button::callback('OK', 'ok')));

$bot->deleteMessage($chatId, $messageId);
$bot->deleteMessages($chatId, [10, 11, 12]);

$bot->forwardMessage($chatId, $fromChatId, $messageId);
$bot->copyMessage($chatId, $fromChatId, $messageId);
```

**Пример ответа (deleteMessage):** `true`

---

## 8. Реакции, закрепление

```php
$bot->setMessageReaction($chatId, $messageId, '👍');
$bot->setMessageReaction($chatId, $messageId, ['🔥', '❤️'], isBig: true);

$bot->pinChatMessage($chatId, $messageId, ['disable_notification' => true]);
$bot->unpinChatMessage($chatId, $messageId);
$bot->unpinAllChatMessages($chatId);
```

---

## 9. Скачивание файлов

```php
$file = $bot->getFile($fileId);                  // { file_id, file_path, file_size }
$bytes = $bot->downloadFile($fileId);            // сырые байты
$bot->downloadFileTo($fileId, '/path/save.jpg'); // возвращает путь назначения
```

**Пример ответа (getFile):**

```json
{ "file_id": "AgACAg...", "file_unique_id": "AQAD…", "file_size": 12345, "file_path": "photos/file_1.jpg" }
```

---

## 10. Администрирование чата

```php
$bot->getChat($chatId);
$bot->getChatMember($chatId, $userId);
$bot->getChatAdministrators($chatId);
$bot->getChatMemberCount($chatId);

$bot->banChatMember($chatId, $userId, ['until_date' => time() + 3600]);
$bot->unbanChatMember($chatId, $userId);
$bot->restrictChatMember($chatId, $userId, ['can_send_messages' => false]);
$bot->promoteChatMember($chatId, $userId, ['can_delete_messages' => true, 'can_restrict_members' => true]);

$bot->setChatTitle($chatId, 'Новое название');
$bot->setChatDescription($chatId, 'Описание');
$bot->createChatInviteLink($chatId, ['member_limit' => 10]);
$bot->exportChatInviteLink($chatId);
$bot->leaveChat($chatId);
```

---

## 11. Настройки и команды бота

```php
$bot->setMyCommands([
    ['command' => 'start', 'description' => 'Запустить бота'],
    ['command' => 'help', 'description' => 'Помощь'],
]);
$bot->getMyCommands();
$bot->deleteMyCommands();

$bot->setMyName('TexHub Bot');
$bot->setMyDescription('Помогаю автоматизировать Telegram.');
$bot->setMyShortDescription('Бот автоматизации');
```

---

## 12. Платежи (инвойсы и Stars)

```php
// Telegram Stars (цифровые товары): валюта "XTR", пустой provider token.
$bot->sendInvoice(
    $chatId,
    title: 'Тариф Pro',
    description: 'Месяц Pro',
    payload: 'order-123',
    currency: 'XTR',
    prices: [['label' => 'Pro', 'amount' => 500]], // 500 Stars
);

// Многоразовая ссылка-инвойс:
$link = $bot->createInvoiceLink('Pro', 'Месячный', 'order-123', 'XTR', [['label' => 'Pro', 'amount' => 500]]);
echo $link->value();   // https://t.me/$invoice...

// В вебхуке: подтвердите pre-checkout, затем обработайте successful_payment:
$bot->answerPreCheckoutQuery($preCheckoutQueryId, true);
$bot->answerShippingQuery($shippingQueryId, true, ['shipping_options' => [...]]);

// Возврат Stars:
$bot->refundStarPayment($userId, $telegramPaymentChargeId);
```

---

## 13. Игры

```php
$bot->sendGame($chatId, 'my_game_short_name');
$bot->setGameScore($userId, 100, ['chat_id' => $chatId, 'message_id' => $messageId]);
$bot->getGameHighScores($userId, ['chat_id' => $chatId, 'message_id' => $messageId]);
```

---

## 14. Вебхуки

```php
// Регистрация (с secret-токеном для безопасности):
$bot->setWebhook('https://app.tj/telegram/webhook', [
    'secret_token' => 'my-webhook-secret',
    'allowed_updates' => ['message', 'callback_query', 'business_message'],
    'drop_pending_updates' => true,
]);

$bot->getWebhookInfo();
$bot->unsetWebhook();          // алиас deleteWebhook()
$bot->deleteWebhook(dropPendingUpdates: true);
```

**Приём** — проверьте secret-заголовок, затем разберите:

```php
$bot->webhooks()->assertValid($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? null);

$update = $bot->webhooks()->parse(file_get_contents('php://input'));
```

**Long polling** (без вебхука):

```php
$offset = 0;
while (true) {
    foreach ($bot->getUpdates(['offset' => $offset, 'timeout' => 30]) as $update) {
        $offset = $update->id() + 1;
        // обработка $update
    }
}
```

---

## 15. Приём апдейтов (объект `Update`)

```php
$update = $bot->webhooks()->parse($rawJson);

$update->id();                  // update_id
$update->type();                // "message" | "callback_query" | "business_message" | …
$update->isMessage();
$update->isCallbackQuery();
$update->isCommand();           // текст начинается с "/"
$update->text();                // текст сообщения ИЛИ данные callback
$update->chatId();
$update->fromId();
$update->from();                // массив пользователя
$update->message();             // массив сообщения (обычное/edited/channel/business)
$update->callbackQuery();
$update->inlineQuery();

// Платежи:
$update->preCheckoutQuery();
$update->shippingQuery();
$update->successfulPayment();

// Business:
$update->isBusiness();
$update->businessConnectionId();
```

**Типичный обработчик вебхука:**

```php
$update = $bot->webhooks()->parse(file_get_contents('php://input'));

if ($update->isCommand() && $update->text() === '/start') {
    $bot->sendMessage($update->chatId(), 'Добро пожаловать! 👋', [
        'reply_markup' => InlineKeyboard::make()->row(Button::callback('Меню', 'menu')),
    ]);
}

if ($update->isCallbackQuery()) {
    $cb = $update->callbackQuery();
    $bot->answerCallbackQuery($cb['id'], ['text' => 'Вы выбрали: ' . $cb['data']]);
}

http_response_code(200);
```

**Пример входящего апдейта (сообщение):**

```json
{ "update_id": 100, "message": { "message_id": 7,
  "from": { "id": 42, "first_name": "Ali", "username": "ali" },
  "chat": { "id": 42, "type": "private" }, "date": 1717250000, "text": "/start" } }
```

### Всё, что может прийти (аксессоры Update)

```php
$update->messageId();   // id входящего сообщения
$update->text();        // текст или данные callback
$update->caption();     // подпись к медиа
$update->photo();       $update->photoFileId();
$update->video();       $update->document();   $update->audio();
$update->voice();       $update->animation();  $update->sticker();  $update->videoNote();
$update->location();    // ['latitude' => .., 'longitude' => ..]
$update->contact();     // ['phone_number' => .., 'first_name' => ..]
$update->venue();       $update->poll();       $update->dice();
$update->fileId();      // первый file_id в сообщении
```

### Бот по-простому — `UpdateHandler`

Самый простой способ написать бота. Расширьте `UpdateHandler` и создайте метод
**с именем команды** — `start` обрабатывает `/start`. Аргумент — это то, что идёт
после команды (например реферальный код из `/start REF123`). Внутри `$this->chat` —
текучий помощник, привязанный к текущему чату.

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
            // Открыли по ссылке t.me/yourbot?start=REF123 — узнаём, кто пригласил:
            $this->chat->message("Код приглашения: {$payload}")->send();
        }

        $this->chat->message('Добро пожаловать! 👋')
            ->keyboard(InlineKeyboard::make()->row(Button::callback('Меню', 'menu')))
            ->send();
    }

    public function help(): void                  { $this->chat->message('Напишите /start')->send(); }

    public function onText(string $text): void    { $this->chat->message('Вы написали: ' . $text)->send(); }
    public function onPhoto(): void               { $this->chat->message('Фото ' . $this->update->photoFileId())->send(); }
    public function onLocation(array $l): void    { $this->chat->message("📍 {$l['latitude']}, {$l['longitude']}")->send(); }
    public function onContact(array $c): void     { $this->chat->message('📱 ' . $c['phone_number'])->send(); }
    public function onCallbackQuery(array $cb): void { $this->answerCallback('OK'); }
}
```

Подключение в вебхуке — одна строка (сам проверяет secret, парсит и маршрутизирует):

```php
(new MyBot)->handleRequest(
    $bot,
    $request->getContent(),
    $request->header('X-Telegram-Bot-Api-Secret-Token'),
);
```

**Команды** ловятся по имени метода: `/start` → `start()`, `/help` → `help()`
(можно и `commandStart()`, или общий `onCommand($cmd, $payload)`).

**События** для переопределения: `onText($text)`, `onPhoto`, `onVideo`, `onDocument`,
`onVoice`, `onAudio`, `onAnimation`, `onSticker`, `onLocation($loc)`, `onContact($c)`,
`onCallbackQuery($cb)`, `onInlineQuery($q)`, `onPreCheckoutQuery($q)`, `onMessage`, `onOther`.

**Внутри хендлера:** `$this->chat` (текучий), `$this->bot`, `$this->update`, и сокращения
`reply()`, `replyPhoto()`, `replyChatAction()`, `answerCallback()`, `chatId()`, `fromId()`.

Текучий `$this->chat` читается естественно:

```php
$this->chat->message('Привет <b>мир</b>')->html()->send();
$this->chat->photo('/path/pic.jpg')->caption('Смотри')->send();
$this->chat->message('Выбор:')->keyboard(InlineKeyboard::make()->row(Button::callback('A', 'a')))->send();
$this->chat->action('typing');
```

---

## 16. Telegram Business

```php
// Входящий бизнес-апдейт:
if ($update->isBusiness()) {
    $connId = $update->businessConnectionId();
    $bot->asBusiness($connId)->sendMessage($update->chatId(), 'Ответ от имени бизнес-аккаунта');
}

// Информация о подключении:
$bot->getBusinessConnection($connId);

// Любой метод отправки принимает business_connection_id напрямую:
$bot->sendMessage($chatId, 'Привет', ['business_connection_id' => $connId]);
```

---

## 17. Мультитенант (много ботов)

```php
$tg = Telegram::fromArray([
    'default' => 'support',
    'bots' => [
        'support' => ['token' => '111:AAA', 'webhook_secret' => 'sA'],
        'sales'   => ['token' => '222:BBB'],
    ],
]);

$tg->driver('support')->sendMessage($chatId, 'Привет из support');
$tg->driver('sales')->sendMessage($chatId, 'Привет из sales');
$tg->sendMessage($chatId, 'Бот по умолчанию');             // проксируется на default

// Бот из токена, загруженного из БД на лету:
$tg->botFromToken($tenant->telegram_token)->sendMessage($chatId, 'Бот арендатора');
```

---

## 18. Любой другой метод API (`call`)

Доступен весь Bot API, даже методы без типизированного хелпера:

```php
$bot->call('setChatPhoto', ['chat_id' => $chatId, 'photo' => InputFile::fromPath('/path/logo.jpg')]);
$bot->call('approveChatJoinRequest', ['chat_id' => $chatId, 'user_id' => $userId]);
$bot->call('getUserProfilePhotos', ['user_id' => $userId]);
```

Возвращает `Response`; используйте `->value()`, `->get('dot.path')`, `->toArray()`, `->boolean()`.

---

## 19. Обработка ошибок

```php
try {
    $bot->sendMessage($chatId, 'Привет');
} catch (ApiException $e) {
    $e->errorCode;      // напр. 429, 400, 403
    $e->getMessage();   // описание от Telegram
    $e->retryAfter();   // секунды ожидания (flood control)
    $e->isRateLimit();
    $e->isUnauthorized();
} catch (TelegramException $e) {
    // транспорт / некорректный ответ / конфиг
}
```

**Пример ответа с ошибкой от Telegram:**

```json
{ "ok": false, "error_code": 429, "description": "Too Many Requests: retry after 5",
  "parameters": { "retry_after": 5 } }
```

---

## 20. Laravel

```bash
php artisan vendor:publish --tag=telegram-config
php artisan vendor:publish --tag=telegram-migrations   # опционально, мультитенант
php artisan migrate
```

### Artisan-команды

Управляйте ботами прямо из терминала:

```bash
# Интерактивно: вставляете токен, он проверяется, предлагается авто-сгенерировать
# секрет вебхука и зарегистрировать вебхук, затем бот сохраняется в БД.
php artisan telegram:bot:add

php artisan telegram:bots                       # список всех ботов (БД + конфиг)
php artisan telegram:webhook:set {bot?}         # URL строится сам из APP_URL; секрет авто
php artisan telegram:webhook:unset {bot?} --drop-pending
php artisan telegram:webhook:info {bot?}        # текущая информация о вебхуке
```

`{bot}` — имя бота или его id в БД (без аргумента берётся бот по умолчанию из конфига).

`telegram:webhook:set` **строит URL автоматически** — `APP_URL` + `telegram.webhook.path`
(по умолчанию `telegram/webhook`) — вручную вводить не нужно. Можно передать URL вручную,
или задать `TELEGRAM_WEBHOOK_URL` глобально. Нужное в `.env`:

```dotenv
APP_URL=https://your-domain.tld
# TELEGRAM_WEBHOOK_PATH=telegram/webhook    # опционально (по умолчанию)
# TELEGRAM_WEBHOOK_URL=https://...          # опционально полный override
# TELEGRAM_WEBHOOK_APPEND_BOT=true          # добавить /{bot} для роутинга по ботам
```

`.env`:

```dotenv
TELEGRAM_BOT_TOKEN=123456:ABC
TELEGRAM_WEBHOOK_SECRET=my-secret
TELEGRAM_PARSE_MODE=HTML
```

```php
use TexHub\Telegram\Laravel\Telegram;

Telegram::sendMessage($chatId, 'Привет из Laravel!');     // бот по умолчанию
Telegram::driver('sales')->sendMessage($chatId, '...');   // именованный бот
```

Бот арендатора из БД:

```php
use TexHub\Telegram\Laravel\Models\TelegramBot;

$record = TelegramBot::create(['name' => 'Acme', 'token' => '999:XYZ', 'webhook_secret' => '...']);
$record->client()->sendMessage($chatId, 'Привет от бота арендатора');
```

### Сохранение чатов (модель `TelegramChat`)

Сохраняйте каждый чат/пользователя, кто пишет боту — `chat_id`, имя, username,
язык, аватар и др.:

```php
use TexHub\Telegram\Laravel\Models\TelegramChat;

// В вебхуке: запоминаем того, кто написал боту:
$chat = TelegramChat::rememberFromUpdate($update, $bot?->id);   // upsert по (bot, chat_id)

// Получить и сохранить аватар (фото профиля), затем URL:
$chat->refreshAvatar($botClient);
$url = $chat->avatarUrl($botClient);

// Связи:
$bot->chats()->where('is_active', true)->get();   // все чаты бота
$chat->bot;                                        // бот-владелец
```

Контроллер вебхука:

```php
public function webhook(string $bot, \Illuminate\Http\Request $request)
{
    $client = Telegram::driver($bot);
    $client->webhooks()->assertValid($request->header('X-Telegram-Bot-Api-Secret-Token'));

    $update = $client->webhooks()->parse($request->getContent());
    // ...обработка

    return response('', 200);
}
```

> Исключите маршрут вебхука из CSRF (`VerifyCsrfToken::$except` / `validateCsrfTokens(except: [...])`).

---

## 21. Тестирование

```php
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Tests\Support\FakeTransport;

$t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 99]]);
$bot = new Bot(new Config('123:ABC'), $t);

$bot->sendMessage(99, 'привет');

// проверяем, что ушло:
$t->last()['params']['text'];   // 'привет'
$t->lastMethod();               // 'sendMessage'
```

---

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
