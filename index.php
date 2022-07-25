<?php

use CrawlerCoinGecko\Factory;
use CrawlerCoinGecko\Writer\RedisWriter;
use \CrawlerCoinGecko\Datastore\Redis;


require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$crawler = Factory::createCrawlerService();
$cmc = Factory::createAlertService();
$size = Redis::get_redis()->dbsize();

try {
    $crawler->invoke();
} catch (Exception $exception) {
    $crawler->getClient()->restart();
}
$currentCoins = $crawler->getCurrentScrappedTokens();

if (empty($currentCoins)) {
    if ($size > 300) {
        Redis::get_redis()->flushall();
    }
    die('Nothing to show' . PHP_EOL);
}

$cmc->sendMessage($currentCoins);
echo 'Downloading information about large movers from last hour ' . date('H:i:s') . PHP_EOL;
echo 'Start saving to Redis ' . date('H:i:s') . PHP_EOL;
RedisWriter::writeToRedis($currentCoins);
echo 'Finish saving to Redis ' . date('H:i:s') . PHP_EOL;

$size = Redis::get_redis()->dbsize();
