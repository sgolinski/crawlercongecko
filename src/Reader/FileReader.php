<?php

namespace CrawlerCoinGecko\Reader;

class FileReader implements Reader
{
    public static function read(): array
    {
        return unserialize(file_get_contents('last_rounded_coins.txt'));
    }

    public static function readSearchCoins(): array
    {
        return unserialize(file_get_contents('last_rounded_coins.txt'));
    }
}