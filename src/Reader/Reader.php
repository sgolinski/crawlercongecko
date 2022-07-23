<?php

namespace CrawlerCoinGecko\Reader;

interface Reader
{
    public static function readTokensFromLastCronJob(): array;

    public static function readTokensAlreadyProcessed(): array;
}