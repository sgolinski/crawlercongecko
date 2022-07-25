<?php

namespace CrawlerCoinGecko\Reader;

use CrawlerCoinGecko\Datastore\Redis;
use CrawlerCoinGecko\Entity\Token;
use CrawlerCoinGecko\ValueObjects\Name;

class RedisReader implements Reader
{
    public static function readTokenByName(string $name): ?Token
    {
        $token = Redis::get_redis()->get($name);
        if ($token) {
            return unserialize($token);
        }
        return null;
    }

    public static function findKey(Name $name): bool
    {
        return Redis::get_redis()->exists($name->asString());
    }
}