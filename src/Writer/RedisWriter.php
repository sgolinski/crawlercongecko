<?php

namespace CrawlerCoinGecko\Writer;

use CrawlerCoinGecko\Entity\Token;
use CrawlerCoinGecko\Redis;

class RedisWriter
{
    public static function writeToRedis(Token $token): void
    {
        Redis::get_redis()->set($token->getName()->asString(), serialize($token));

    }

}