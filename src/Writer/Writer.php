<?php

namespace CrawlerCoinGecko\Writer;

interface Writer
{
    public static function writeToRedis(array $tokens): void;
}