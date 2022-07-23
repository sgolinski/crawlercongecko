<?php

namespace CrawlerCoinGecko\Writer;


interface Writer
{
    public static function writeTokensFromLastCronJob(array $tokens): void;

    public static function writeTokensToListTokensAlreadyProcessed(array $tokens): void;
}