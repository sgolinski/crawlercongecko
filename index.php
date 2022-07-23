<?php

use CrawlerCoinGecko\service\AlertService;
use CrawlerCoinGecko\service\CrawlerService;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = new CrawlerService();
$cmc = new AlertService();

$crawler->invoke();
$currentCoins = $crawler->getTokensWithInformations();

if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}

$cmc->sendMessageWhenIsBsc($currentCoins);

echo 'Downloading information about large movers from last hour ' . date("F j, Y, g:i a") . PHP_EOL;
