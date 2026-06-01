<?php

declare(strict_types=1);

namespace TexHub\Telegram\Http;

use TexHub\Telegram\Config;
use TexHub\Telegram\Exceptions\ApiException;
use TexHub\Telegram\Exceptions\TelegramException;
use TexHub\Telegram\InputFile;

/**
 * Low-level Bot API caller: posts a method, decodes `{ok,result}` and raises
 * {@see ApiException} on `ok: false`.
 */
final class ApiClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * Call a Bot API method and return the raw `result`.
     *
     * @param array<string, mixed> $params
     *
     * @return mixed
     */
    public function call(string $method, array $params = [])
    {
        $params = array_filter($params, static fn ($v) => $v !== null);
        $multipart = $this->hasFile($params);

        $raw = $this->transport->post($this->config->methodUrl($method), $params, $multipart);

        $decoded = json_decode($raw->body, true);

        if (! is_array($decoded)) {
            throw new TelegramException('Unexpected non-JSON response from Telegram: ' . substr($raw->body, 0, 200));
        }

        if (($decoded['ok'] ?? false) !== true) {
            throw ApiException::fromResponse($decoded);
        }

        return $decoded['result'] ?? null;
    }

    public function download(string $filePath): string
    {
        return $this->transport->get($this->config->fileUrl($filePath))->body;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function hasFile(array $params): bool
    {
        foreach ($params as $value) {
            if ($value instanceof InputFile) {
                return true;
            }
        }

        return false;
    }
}
