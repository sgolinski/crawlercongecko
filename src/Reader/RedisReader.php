<?php

namespace CrawlerCoinGecko\Reader;

use CrawlerCoinGecko\Datastore\Redis;
use CrawlerCoinGecko\Entity\Token;

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
}