<?php

declare(strict_types=1);

namespace TexHub\Telegram\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\Telegram\Bot;
use TexHub\Telegram\Config;
use TexHub\Telegram\Handler\UpdateHandler;
use TexHub\Telegram\Tests\Support\FakeTransport;
use TexHub\Telegram\Update;

final class HandlerTest extends TestCase
{
    public function test_update_extracts_all_attachment_types(): void
    {
        $u = new Update(['message' => [
            'message_id' => 5,
            'chat' => ['id' => 1], 'from' => ['id' => 9],
            'location' => ['latitude' => 38.5, 'longitude' => 68.7],
        ]]);
        $this->assertSame(['latitude' => 38.5, 'longitude' => 68.7], $u->location());
        $this->assertSame(5, $u->messageId());

        $contact = new Update(['message' => ['chat' => ['id' => 1], 'contact' => ['phone_number' => '992900', 'first_name' => 'Ali']]]);
        $this->assertSame('992900', $contact->contact()['phone_number']);

        $photo = new Update(['message' => ['chat' => ['id' => 1], 'photo' => [['file_id' => 'small'], ['file_id' => 'big']]]]);
        $this->assertSame('big', $photo->photoFileId());
        $this->assertSame('big', $photo->fileId());

        $doc = new Update(['message' => ['chat' => ['id' => 1], 'document' => ['file_id' => 'doc1', 'file_name' => 'x.pdf']]]);
        $this->assertSame('doc1', $doc->document()['file_id']);
        $this->assertSame('doc1', $doc->fileId());
    }

    public function test_handler_routes_command_text_and_attachments(): void
    {
        $t = new FakeTransport();
        $t->willReturn(['message_id' => 1, 'chat' => ['id' => 1]]) // command reply
          ->willReturn(['message_id' => 2, 'chat' => ['id' => 1]]) // text reply
          ->willReturn(['message_id' => 3, 'chat' => ['id' => 1]]) // location reply
          ->willReturn(true);                                      // answerCallback

        $bot = new Bot(new Config('123:ABC'), $t);

        $handler = new class extends UpdateHandler {
            public array $log = [];
            // Simple command method name (the beginner-friendly style):
            public function start(string $payload): void { $this->log[] = "cmd:start:$payload"; $this->chat->message('hi')->send(); }
            protected function onText(string $text): void { $this->log[] = "text:$text"; $this->reply('echo'); }
            protected function onLocation(array $loc): void { $this->log[] = 'loc:' . $loc['latitude']; $this->reply('got'); }
            protected function onCallbackQuery(array $cb): void { $this->log[] = 'cb:' . $cb['data']; $this->answerCallback('ok'); }
        };

        $handler->handle($bot, new Update(['message' => ['chat' => ['id' => 1], 'text' => '/start hello']]));
        $handler->handle($bot, new Update(['message' => ['chat' => ['id' => 1], 'text' => 'plain message']]));
        $handler->handle($bot, new Update(['message' => ['chat' => ['id' => 1], 'location' => ['latitude' => 38.5, 'longitude' => 68.7]]]));
        $handler->handle($bot, new Update(['callback_query' => ['id' => 'c1', 'data' => 'yes', 'message' => ['chat' => ['id' => 1]]]]));

        $this->assertSame(['cmd:start:hello', 'text:plain message', 'loc:38.5', 'cb:yes'], $handler->log);
        // command reply went out as sendMessage
        $this->assertSame('hi', $t->history[0]['params']['text']);
    }

    public function test_fluent_chat_builder(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 5]]);
        $bot = new Bot(new Config('123:ABC'), $t);

        $bot->chat(5)->message('Hello <b>world</b>')->html()->send();

        $this->assertSame('sendMessage', $t->lastMethod());
        $this->assertSame('Hello <b>world</b>', $t->last()['params']['text']);
        $this->assertSame('HTML', $t->last()['params']['parse_mode']);
        $this->assertSame(5, $t->last()['params']['chat_id']);

        // photo with caption
        $bot->chat(5)->photo('https://x/p.jpg')->caption('Look')->send();
        $this->assertSame('sendPhoto', $t->lastMethod());
        $this->assertSame('Look', $t->last()['params']['caption']);
    }

    public function test_handler_request_verifies_secret(): void
    {
        $t = (new FakeTransport())->willReturn(['message_id' => 1, 'chat' => ['id' => 1]]);
        $bot = new Bot(new Config('123:ABC', webhookSecret: 'sek'), $t);

        $handler = new class extends UpdateHandler {
            public bool $handled = false;
            protected function onText(string $text): void { $this->handled = true; }
        };

        $handler->handleRequest($bot, json_encode(['message' => ['chat' => ['id' => 1], 'text' => 'hi']]), 'sek');
        $this->assertTrue($handler->handled);

        $this->expectException(\TexHub\Telegram\Exceptions\InvalidWebhookException::class);
        $handler->handleRequest($bot, '{}', 'WRONG');
    }
}
