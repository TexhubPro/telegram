<?php

declare(strict_types=1);

namespace TexHub\Telegram\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the Telegram manager.
 *
 * @method static \TexHub\Telegram\Bot driver(?string $name = null)
 * @method static \TexHub\Telegram\Bot botFromToken(string $token, array $extra = [])
 * @method static \TexHub\Telegram\Telegram register(string $name, \TexHub\Telegram\Config $config)
 * @method static array names()
 *
 * @see \TexHub\Telegram\Telegram
 */
class Telegram extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'telegram';
    }
}
