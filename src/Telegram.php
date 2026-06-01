<?php

declare(strict_types=1);

namespace TexHub\Telegram;

use TexHub\Telegram\Exceptions\ConfigurationException;
use TexHub\Telegram\Http\Transport;

/**
 * Entry point and multi-bot manager.
 *
 * Single bot:
 * ```php
 * $bot = Telegram::bot('123:ABC');
 * $bot->sendMessage($chatId, 'Hi');
 * ```
 *
 * Multi-tenant (many bots) — register named configs, or build a bot from any
 * token at runtime (e.g. loaded from the database):
 * ```php
 * $tg = Telegram::fromArray(['bots' => ['support' => ['token' => '...'], 'sales' => ['token' => '...']]]);
 * $tg->bot('support')->sendMessage(...);
 * $tg->botFromToken($tenant->telegram_token)->sendMessage(...);
 * ```
 */
final class Telegram
{
    /** @var array<string, Config> */
    private array $configs = [];

    private ?string $default = null;

    /** @var array<string, Bot> */
    private array $resolved = [];

    public function __construct(
        private readonly ?Transport $transport = null,
    ) {
    }

    /**
     * Quickly build a single bot from a token.
     */
    public static function bot(string $token, ?Transport $transport = null): Bot
    {
        return new Bot(new Config($token), $transport);
    }

    /**
     * Build a manager from a config array:
     * `['default' => 'support', 'bots' => ['support' => ['token' => ...], ...]]`.
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, ?Transport $transport = null): self
    {
        $manager = new self($transport);

        foreach ((array) ($config['bots'] ?? []) as $name => $botConfig) {
            if (is_array($botConfig) && ! empty($botConfig['token'])) {
                $manager->configs[$name] = Config::fromArray($botConfig);
            }
        }

        $manager->default = isset($config['default']) ? (string) $config['default'] : array_key_first($manager->configs);

        return $manager;
    }

    /**
     * Register a named bot config.
     */
    public function register(string $name, Config $config): self
    {
        $this->configs[$name] = $config;
        $this->default ??= $name;
        unset($this->resolved[$name]);

        return $this;
    }

    /**
     * Resolve a named bot (or the default bot when $name is null).
     */
    public function driver(?string $name = null): Bot
    {
        $name ??= $this->default;

        if ($name === null || ! isset($this->configs[$name])) {
            throw new ConfigurationException(sprintf('Telegram bot "%s" is not configured.', (string) $name));
        }

        return $this->resolved[$name] ??= new Bot($this->configs[$name], $this->transport);
    }

    /**
     * Build a bot from a raw token at runtime (multi-tenant / DB-driven).
     *
     * @param array<string, mixed> $extra Additional config (webhook_secret, parse_mode, …).
     */
    public function botFromToken(string $token, array $extra = []): Bot
    {
        return new Bot(Config::fromArray(['token' => $token] + $extra), $this->transport);
    }

    /**
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->configs);
    }

    /**
     * Forward unknown calls to the default bot, so you can call
     * `$telegram->sendMessage(...)` directly.
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->driver()->{$method}(...$arguments);
    }
}
