<?php

namespace CrawlerCoinGecko\Reader;

use CrawlerCoinGecko\Entity\Token;
use CrawlerCoinGecko\ValueObjects\Name;

interface Reader
{
    public static function readTokenByName(string $name): ?Token;

    public static function findKey(Name $name): bool;
}