<?php

use CrawlerCoinGecko\Factory;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$cmc = Factory::createAlertService();

$crawler->invoke();
$currentCoins = $crawler->getTokensWithInformation();

if (empty($currentCoins)) {
    $crawler->getClient()->quit();
    die('Nothing to show' . PHP_EOL);
}

$cmc->sendMessageWhenIsBsc($currentCoins);

echo 'Downloading information about large movers from last hour ' . date('H:i:s') . PHP_EOL;
