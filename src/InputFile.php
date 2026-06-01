<?php

declare(strict_types=1);

namespace TexHub\Telegram;

use TexHub\Telegram\Exceptions\ConfigurationException;

/**
 * A local file to upload (photo, document, video, …). Pass it as the media
 * value and the SDK switches to a multipart request automatically.
 *
 * For files already on Telegram or remote URLs, just pass the file_id / URL
 * string instead of an InputFile.
 */
final class InputFile
{
    private function __construct(
        public readonly ?string $path,
        public readonly ?string $contents,
        public readonly string $filename,
        public readonly ?string $mimeType,
    ) {
    }

    public static function fromPath(string $path, ?string $filename = null, ?string $mimeType = null): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new ConfigurationException(sprintf('File "%s" does not exist or is not readable.', $path));
        }

        return new self($path, null, $filename ?? basename($path), $mimeType);
    }

    public static function fromContents(string $contents, string $filename, ?string $mimeType = null): self
    {
        return new self(null, $contents, $filename, $mimeType);
    }
}
