<?php

declare(strict_types=1);

namespace TexHub\Telegram\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Enums\ChatAction;
use TexHub\Telegram\Exceptions\ApiException;
use TexHub\Telegram\InputFile;
use TexHub\Telegram\Keyboard\Button;
use TexHub\Telegram\Keyboard\InlineKeyboard;
use TexHub\Telegram\Tests\Support\FakeTransport;

final class BotTest extends TestCase
{
    private function bot(FakeTransport $t, ?Config $config = null): Bot
    {
        return new Bot($config ?? new Config(token: '123:ABC', defaultParseMode: 'HTML'), $t);
    }

    public function test_send_message_with_default_parse_mode(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 10, 'chat' => ['id' => 555], 'text' => 'Hi']);

        $response = $this->bot($t)->sendMessage(555, 'Hi');

        $this->assertSame(10, $response->messageId());
        $this->assertSame(555, $response->chatId());

        $req = $t->last();
        $this->assertStringEndsWith('/bot123:ABC/sendMessage', $req['url']);
        $this->assertSame(555, $req['params']['chat_id']);
        $this->assertSame('Hi', $req['params']['text']);
        $this->assertSame('HTML', $req['params']['parse_mode']);
    }

    public function test_inline_keyboard_is_serialized(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 1]]);

        $this->bot($t)->sendMessage(1, 'Pick', [
            'reply_markup' => InlineKeyboard::make()
                ->row(Button::callback('Yes', 'yes'), Button::callback('No', 'no'))
                ->row(Button::url('Open', 'https://texhub.pro')),
        ]);

        $markup = $t->last()['params']['reply_markup'];
        $this->assertSame('yes', $markup['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('https://texhub.pro', $markup['inline_keyboard'][1][0]['url']);
    }

    public function test_send_photo_with_local_file_uses_multipart(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tg');
        file_put_contents($tmp, 'IMG');

        $t = (new FakeTransport())->willReturn(['message_id' => 2, 'chat' => ['id' => 9]]);

        $this->bot($t)->sendPhoto(9, InputFile::fromPath($tmp), ['caption' => 'Hello']);

        $this->assertTrue($t->last()['multipart']);
        $this->assertInstanceOf(InputFile::class, $t->last()['params']['photo']);

        unlink($tmp);
    }

    public function test_chat_action_enum(): void
    {
        $t = (new FakeTransport())->willReturn(true);

        $this->bot($t)->sendChatAction(1, ChatAction::Typing);

        $this->assertSame('typing', $t->last()['params']['action']);
    }

    public function test_business_connection_id_injected(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 3, 'chat' => ['id' => 1]]);

        $this->bot($t)->asBusiness('bizconn_1')->sendMessage(1, 'On behalf of business');

        $this->assertSame('bizconn_1', $t->last()['params']['business_connection_id']);
    }

    public function test_set_webhook_uses_config_secret(): void
    {
        $t = (new FakeTransport())->willReturn(true);
        $config = new Config(token: '123:ABC', webhookSecret: 'sec123');

        $this->bot($t, $config)->setWebhook('https://app.tj/tg/webhook');

        $this->assertSame('sec123', $t->last()['params']['secret_token']);
    }

    public function test_download_file(): void
    {
        $t = new FakeTransport();
        $t->willReturn(['file_id' => 'f1', 'file_path' => 'photos/file_1.jpg'])->willDownload('BINARY');

        $bytes = $this->bot($t)->downloadFile('f1');

        $this->assertSame('BINARY', $bytes);
        $this->assertStringContainsString('/file/bot123:ABC/photos/file_1.jpg', $t->last()['url']);
    }

    public function test_api_error_throws(): void
    {
        $t = (new FakeTransport())->willFail(429, 'Too Many Requests', ['retry_after' => 5]);

        try {
            $this->bot($t)->sendMessage(1, 'x');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertTrue($e->isRateLimit());
            $this->assertSame(5, $e->retryAfter());
        }
    }
}
