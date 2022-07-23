<?php

namespace CrawlerCoinGecko\Writer;


interface Writer
{
    public static function write(array $makers): void;
}