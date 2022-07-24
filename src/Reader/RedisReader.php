<?php

namespace CrawlerCoinGecko\Reader;

use CrawlerCoinGecko\Entity\Token;
use CrawlerCoinGecko\Redis;

class RedisReader
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