<?php

declare(strict_types=1);

namespace TexHub\Telegram\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\Telegram\Config;
use TexHub\Telegram\Exceptions\ConfigurationException;
use TexHub\Telegram\Exceptions\InvalidWebhookException;
use TexHub\Telegram\Telegram;
use TexHub\Telegram\Tests\Support\FakeTransport;
use TexHub\Telegram\Webhook\WebhookHandler;

final class WebhookAndManagerTest extends TestCase
{
    public function test_webhook_secret_verification(): void
    {
        $handler = new WebhookHandler(new Config(token: '1:A', webhookSecret: 'topsecret'));

        $this->assertTrue($handler->verify('topsecret'));
        $this->assertFalse($handler->verify('wrong'));

        $this->expectException(InvalidWebhookException::class);
        $handler->assertValid('wrong');
    }

    public function test_parse_message_update(): void
    {
        $handler = new WebhookHandler(new Config(token: '1:A'));

        $update = $handler->parse(json_encode([
            'update_id' => 1,
            'message' => [
                'message_id' => 7,
                'from' => ['id' => 42, 'first_name' => 'Ali'],
                'chat' => ['id' => 100, 'type' => 'private'],
                'text' => '/start',
            ],
        ]));

        $this->assertSame('message', $update->type());
        $this->assertTrue($update->isMessage());
        $this->assertTrue($update->isCommand());
        $this->assertSame('/start', $update->text());
        $this->assertSame(100, $update->chatId());
        $this->assertSame(42, $update->fromId());
    }

    public function test_parse_callback_query(): void
    {
        $handler = new WebhookHandler(new Config(token: '1:A'));

        $update = $handler->parse([
            'update_id' => 2,
            'callback_query' => [
                'id' => 'cb1',
                'from' => ['id' => 5],
                'data' => 'yes',
                'message' => ['chat' => ['id' => 200]],
            ],
        ]);

        $this->assertTrue($update->isCallbackQuery());
        $this->assertSame('yes', $update->text());
        $this->assertSame(200, $update->chatId());
    }

    public function test_parse_business_message(): void
    {
        $handler = new WebhookHandler(new Config(token: '1:A'));

        $update = $handler->parse([
            'update_id' => 3,
            'business_message' => [
                'message_id' => 1,
                'business_connection_id' => 'bizconn_1',
                'chat' => ['id' => 300],
                'text' => 'Hello business',
            ],
        ]);

        $this->assertTrue($update->isBusiness());
        $this->assertSame('bizconn_1', $update->businessConnectionId());
        $this->assertSame('Hello business', $update->text());
    }

    public function test_manager_multi_bot(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 1]]);

        $tg = Telegram::fromArray([
            'default' => 'support',
            'bots' => [
                'support' => ['token' => '111:SUP'],
                'sales' => ['token' => '222:SAL'],
            ],
        ], $t);

        $tg->driver('sales')->sendMessage(1, 'hi');
        $this->assertStringContainsString('/bot222:SAL/', $t->last()['url']);

        // default + __call forwarding
        $tg->sendMessage(1, 'default bot');
        $this->assertStringContainsString('/bot111:SUP/', $t->last()['url']);

        // runtime token (multi-tenant)
        $tg->botFromToken('999:DYN', [])->getMe();
        $this->assertStringContainsString('/bot999:DYN/getMe', $t->last()['url']);
    }

    public function test_unknown_bot_throws(): void
    {
        $this->expectException(ConfigurationException::class);
        Telegram::fromArray(['bots' => []], new FakeTransport())->driver('nope');
    }
}
