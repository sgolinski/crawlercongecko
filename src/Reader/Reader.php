<?php

namespace CrawlerCoinGecko\Reader;

interface Reader
{
    public static function read(): array;
}