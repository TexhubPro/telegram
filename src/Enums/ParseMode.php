<?php

declare(strict_types=1);

namespace TexHub\Telegram\Enums;

/**
 * Text formatting modes.
 */
enum ParseMode: string
{
    case Html = 'HTML';
    case Markdown = 'Markdown';
    case MarkdownV2 = 'MarkdownV2';
}
