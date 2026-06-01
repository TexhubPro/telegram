<?php

declare(strict_types=1);

namespace TexHub\Telegram\Http;

use TexHub\Telegram\Exceptions\TransportException;

/**
 * HTTP transport abstraction so the SDK has no hard dependency on a specific
 * HTTP client and can be fully unit-tested with a fake.
 */
interface Transport
{
    /**
     * Perform a POST request. If $multipart is true, $params is sent as
     * multipart/form-data (values may be {@see \TexHub\Telegram\InputFile});
     * otherwise as JSON.
     *
     * @param array<string, mixed> $params
     *
     * @throws TransportException
     */
    public function post(string $url, array $params = [], bool $multipart = false): RawResponse;

    /**
     * Perform a raw GET (used for downloading files). Returns the raw bytes.
     *
     * @throws TransportException
     */
    public function get(string $url): RawResponse;
}
