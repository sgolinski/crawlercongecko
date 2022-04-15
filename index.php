<?php

use CrawlerCoinGecko\Coingecko;
use CrawlerCoinGecko\Crawler;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = new Crawler();
$cmc = new Coingecko();
$crawler->invoke();
$currentCoins = $crawler->getReturnArray();

if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}

file_put_contents('last_rounded_coins.txt', serialize($currentCoins));

$cmc->invoke($currentCoins);

echo 'Downloading information about large movers from last hour ' . date("F j, Y, g:i a") . PHP_EOL;

sleep(45);