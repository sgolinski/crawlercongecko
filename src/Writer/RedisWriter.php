<?php

namespace CrawlerCoinGecko\Writer;

use CrawlerCoinGecko\Entity\Token;
use CrawlerCoinGecko\Redis;

class RedisWriter
{
    public static function writeToRedis(array $tokens): void
    {
        foreach ($tokens as $token) {
            if (!$token->isProccessed()) {
                Redis::get_redis()->set($token->getName()->asString(), serialize($token));
            }
        }

    }

}