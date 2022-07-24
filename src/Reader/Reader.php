<?php

namespace CrawlerCoinGecko\Reader;

use CrawlerCoinGecko\Entity\Token;

interface Reader
{
    public static function readTokenByName(string $name): ?Token;
}