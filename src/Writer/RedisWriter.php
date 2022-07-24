<?php

namespace CrawlerCoinGecko\Writer;

use CrawlerCoinGecko\Redis;

class RedisWriter
{
    public static function writeToRedis(array $tokens): void
    {
        foreach ($tokens as $token) {
            if (!$token->isProccessed()) {
                $token->setProcessed();
                Redis::get_redis()->set($token->getName()->asString(), serialize($token));
            }
        }

    }

}