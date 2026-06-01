<?php

declare(strict_types=1);

namespace TexHub\Telegram\Http;

use CURLFile;
use TexHub\Telegram\Exceptions\TransportException;
use TexHub\Telegram\InputFile;

/**
 * Default {@see Transport} implementation built on the cURL extension.
 * Supports JSON bodies and multipart uploads (for InputFile).
 */
final class CurlTransport implements Transport
{
    public function __construct(
        private readonly int $timeout = 30,
        private readonly string $userAgent = 'texhub-telegram/1.0 (+https://texhub.pro)',
    ) {
    }

    public function post(string $url, array $params = [], bool $multipart = false): RawResponse
    {
        $ch = curl_init();
        $headers = [];

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
        ];

        if ($multipart) {
            $options[CURLOPT_POSTFIELDS] = $this->buildMultipart($params);
        } else {
            $encoded = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new TransportException('Failed to JSON-encode the request body: ' . json_last_error_msg());
            }
            $options[CURLOPT_POSTFIELDS] = $encoded;
            $headers[] = 'Content-Type: application/json';
        }

        if ($headers !== []) {
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errorNo !== 0 || $body === false) {
            throw new TransportException(sprintf('Telegram request to %s failed: %s', $this->scrub($url), $error ?: 'unknown cURL error'));
        }

        return new RawResponse($statusCode, (string) $body);
    }

    public function get(string $url): RawResponse
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $body = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errorNo !== 0 || $body === false) {
            throw new TransportException(sprintf('Telegram download from %s failed: %s', $this->scrub($url), $error ?: 'unknown cURL error'));
        }

        return new RawResponse($statusCode, (string) $body);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function buildMultipart(array $params): array
    {
        $fields = [];
        foreach ($params as $key => $value) {
            if ($value instanceof InputFile) {
                if ($value->path !== null) {
                    $fields[$key] = new CURLFile($value->path, $value->mimeType ?? '', $value->filename);
                } else {
                    $tmp = tempnam(sys_get_temp_dir(), 'tg');
                    file_put_contents((string) $tmp, $value->contents);
                    $fields[$key] = new CURLFile((string) $tmp, $value->mimeType ?? '', $value->filename);
                }
            } elseif (is_array($value)) {
                $fields[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $fields[$key] = $value ? 'true' : 'false';
            } else {
                $fields[$key] = (string) $value;
            }
        }

        return $fields;
    }

    /**
     * Hide the bot token from error messages / logs.
     */
    private function scrub(string $url): string
    {
        return (string) preg_replace('#/bot[0-9]+:[A-Za-z0-9_-]+#', '/bot***', $url);
    }
}
