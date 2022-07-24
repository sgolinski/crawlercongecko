<?php

namespace CrawlerCoinGecko;

use CrawlerCoinGecko\Entity\BscToken;
use CrawlerCoinGecko\Service\Alert;
use CrawlerCoinGecko\Service\Crawler;
use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;


class Factory
{
    public static function createCrawlerService(): Crawler
    {
        return new Crawler();
    }

    public static function createAlertService(): Alert
    {
        return new Alert();
    }

    public static function createBscToken(
        Name        $name,
        Price       $price,
        DropPercent $percent,
        Url         $url,
        Address     $address,
        int         $created,
        Chain       $chain,
        bool        $processed
    ): BscToken
    {
        return new BscToken($name, $price, $percent, $url, $address, $created, $chain, $processed);
    }

    public static function createSlackClient(string $hook): SlackClient
    {
        return new SlackClient($hook);
    }

    public static function createSlackMessage(): Message
    {
        return new Message();
    }

}