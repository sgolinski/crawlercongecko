<?php

namespace CrawlerCoinGecko\service;

use ArrayIterator;
use CrawlerCoinGecko\BscToken;
use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;
use CrawlerCoinGecko\Writer\FileWriter;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class CrawlerService
{
    private PantherClient $client;

    private static array $lastRoundedCoins;

    private array $tokensWithInformations = [];

    private array $tokensWithoutInformation = [];

    public static array $recorded_coins;

    private const URL = 'https://www.coingecko.com/en/crypto-gainers-losers?time=h1';

    public function __construct()
    {
        self::$lastRoundedCoins = [];
        self::$recorded_coins = [];
    }


    public function invoke()
    {
        try {
            $this->startClient();
            $content = $this->getContent();
            $this->assignElementsFromContent($content);
            $this->assignDetailInformationToCoin();

            $uniqueLastRoundedCoins = $this->removeOldTokensAndRemoveDuplicates(self::$lastRoundedCoins);
            FileWriter::write($uniqueLastRoundedCoins);
            FileWriter::writeAlreadyShown(self::$recorded_coins);

        } catch (Exception $exception) {
            echo $exception->getFile() . ' ' . $exception->getLine() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    private function getContent(): ArrayIterator
    {
        $list = null;
        try {
            $list = $this->client->getCrawler()
                ->filter('body > div.container >div:nth-child(7)> div:nth-child(2)')
                ->filter('#gecko-table-all > tbody')
                ->children()
                ->getIterator();
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return $list;
    }

    private function assignElementsFromContent(ArrayIterator $content)
    {
        foreach ($content as $webElement) {

            try {

                assert($webElement instanceof RemoteWebElement);
                $percent = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat((float)$percent);

                if ($percent->asFloat() < 5) {
                    continue;
                }

                $name = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(1)'))
                    ->findElement(WebDriverBy::tagName('div'))
                    ->findElement(WebDriverBy::cssSelector('div:nth-child(1)'))->getText();
                $name = Name::fromString($name);

                $fromLastRound = $this->checkIfTokenIsNotFromLastRound($name);

                if ($fromLastRound) {
                    continue;
                }
                $find = $this->checkIfIsNotRecorded($name);

                if ($find) {
                    $currentTimestamp = time();
                    $find->setDropPercent($percent);
                    $find->setCreated($currentTimestamp);
                    $this->tokensWithInformations[] = $find;
                } else {
                    $url = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(2)'))
                        ->findElement(WebDriverBy::tagName('a'))
                        ->getAttribute('href');
                    $url = Url::fromString($url);

                    $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                        ->getText();
                    $price = Price::fromFloat((float)$price);

                    $currentTimestamp = time();
                    $address = Address::fromString('');
                    $chain = Chain::fromString('');
                    $this->tokensWithoutInformation[] = new BscToken($name, $price, $percent, $url, $address, $currentTimestamp, $chain);
                }
            } catch
            (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }
        }
    }

    private function assignDetailInformationToCoin()
    {
        foreach ($this->tokensWithoutInformation as $token) {

            assert($token instanceof BscToken);
            $this->client->get($token->getUrl()->asString());
            $this->client->refreshCrawler();


            $address = $this->client->getCrawler()
                ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                ->getAttribute('data-address');

            $address = Address::fromString($address);

            $chain = $this->client->getCrawler()
                ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                ->getAttribute('data-chain-id');


            if ($address != '' && $chain == '56') {
                $chain = Chain::fromString('bsc');
                $newToken = new BscToken($token->getName(), $token->getPrice(), $token->getPercent(), $token->getUrl(), $address, $token->getCreated(), $chain);
                $this->tokensWithInformations[] = $newToken;
                self::$lastRoundedCoins[] = $newToken;
                self::$recorded_coins[] = $newToken;
            }
        }
    }

    public function getClient(): PantherClient
    {
        return $this->client;
    }

    private function checkIfTokenIsNotFromLastRound($name): bool
    {
        $currentTime = time();
        foreach (self::$lastRoundedCoins as $showedAlreadyToken) {
            assert($showedAlreadyToken instanceof BscToken);
            if ($showedAlreadyToken->getName()->asString() === $name->asString()) {
                if ($currentTime - $showedAlreadyToken->getCreated() > 3600) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    private function checkIfIsNotRecorded($name): ?BscToken
    {

        foreach (self::$recorded_coins as $existedToken) {
            assert($existedToken instanceof BscToken);
            if ($existedToken->getName()->asString() === $name->asString()) {
                return $existedToken;
            }
        }
        return null;
    }

    /**
     * @return void
     */
    public function startClient(): void
    {
        echo "Start crawling " . date("F j, Y, g:i:s a") . PHP_EOL;
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get(self::URL);
    }

    private function removeOldTokensAndRemoveDuplicates(array $lastRoundedCoins): array
    {
        {
            $uniqueArray = [];
            $currentTime = time();
            foreach ($lastRoundedCoins as $token) {
                assert($token instanceof BscToken);
                if (empty($notUnique)) {
                    $uniqueArray[] = $token;
                }

                foreach ($uniqueArray as $uniqueProve) {
                    if ($token->getName()->asString() === $uniqueProve->getName()->asString()) {
                        if ($currentTime - $token->getCreated() > 7200) {
                            continue;
                        }
                        if ($token->getCreated() > $uniqueProve->created) {
                            $uniqueProve->setCreated($token->created);
                            continue;
                        }
                    }
                }
                $uniqueArray[] = $token;
            }
            return $uniqueArray;
        }
    }

    /**
     * @return array
     */
    public function getTokensWithInformations(): array
    {
        return $this->tokensWithInformations;
    }


}