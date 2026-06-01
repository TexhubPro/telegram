<?php

declare(strict_types=1);

namespace TexHub\Telegram\Tests\Support;

use TexHub\Telegram\Http\RawResponse;
use TexHub\Telegram\Http\Transport;

/**
 * In-memory transport for tests: records requests and returns queued responses.
 */
final class FakeTransport implements Transport
{
    /** @var array<int, array{url: string, params: array, multipart: bool}> */
    public array $history = [];

    /** @var array<int, RawResponse> */
    private array $queue = [];

    private string $getBody = '';

    /**
     * Queue a successful `result` payload.
     */
    public function willReturn(mixed $result): self
    {
        $this->queue[] = new RawResponse(200, (string) json_encode(['ok' => true, 'result' => $result]));

        return $this;
    }

    /**
     * Queue an API error.
     */
    public function willFail(int $code, string $description, array $parameters = []): self
    {
        $this->queue[] = new RawResponse(200, (string) json_encode([
            'ok' => false, 'error_code' => $code, 'description' => $description, 'parameters' => $parameters,
        ]));

        return $this;
    }

    public function willDownload(string $bytes): self
    {
        $this->getBody = $bytes;

        return $this;
    }

    public function post(string $url, array $params = [], bool $multipart = false): RawResponse
    {
        $this->history[] = compact('url', 'params', 'multipart');

        return $this->queue !== [] ? array_shift($this->queue) : new RawResponse(200, '{"ok":true,"result":true}');
    }

    public function get(string $url): RawResponse
    {
        $this->history[] = ['url' => $url, 'params' => [], 'multipart' => false];

        return new RawResponse(200, $this->getBody);
    }

    public function last(): array
    {
        return $this->history[count($this->history) - 1];
    }

    public function lastMethod(): string
    {
        $url = $this->last()['url'];

        return (string) substr($url, (int) strrpos($url, '/') + 1);
    }
}
