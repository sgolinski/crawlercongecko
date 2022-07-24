<?php

namespace CrawlerCoinGecko\Writer;

use CrawlerCoinGecko\Datastore\Redis;
use CrawlerCoinGecko\Entity\Token;

class RedisWriter implements Writer
{
    public static function writeToRedis(array $tokens): void
    {
        foreach ($tokens as $token) {
            assert($token instanceof Token);
            if (!$token->isProcessed()) {
                $token->setProcessed();
                Redis::get_redis()->set($token->getName()->asString(), serialize($token));
            }
            Redis::get_redis()->save();
        }
    }
}