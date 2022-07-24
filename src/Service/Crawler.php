<?php

namespace CrawlerCoinGecko\Service;

use ArrayIterator;
use CrawlerCoinGecko\Factory;
use CrawlerCoinGecko\Reader\RedisReader;
use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;
use CrawlerCoinGecko\Entity\Token;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;

    private array $currentScrappedTokens = [];

    private const URL = 'https://www.coingecko.com/en/crypto-gainers-losers?time=h1';

    public function invoke(): void
    {
        try {
            $this->startClient();
            $content = $this->getContent();
            $this->createTokensFromContent($content);
            $this->assignChainAndAddress();

        } catch (Exception $exception) {
            echo $exception->getFile() . ' ' . $exception->getLine() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    private function getContent(): ?ArrayIterator
    {
        echo 'Start getting content ' . date('H:i:s', time()) . PHP_EOL;
        $list = null;

        try {
            $list = $this->client->getCrawler()
                ->filter('body > div.container >div:nth-child(7)> div:nth-child(2)')
                ->filter('#gecko-table-all > tbody > tr:nth-child(-n+10)')
                ->getIterator();

        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        echo 'Content downloaded ' . date('H:i:s', time()) . PHP_EOL;
        return $list;
    }

    private function createTokensFromContent(ArrayIterator $content): void
    {
        echo 'Start creating tokens from content ' . date('H:i:s', time()) . PHP_EOL;

        foreach ($content as $webElement) {
            try {
                assert($webElement instanceof RemoteWebElement);
                $percent = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                    ->getText();
                $percent = DropPercent::fromFloat((float)$percent);

                if ($percent->asFloat() > -19.0) {
                    continue;
                }

                $name = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(1)'))
                    ->findElement(WebDriverBy::tagName('div'))
                    ->findElement(WebDriverBy::cssSelector('div:nth-child(1)'))->getText();
                $name = Name::fromString($name);

                $token = RedisReader::readTokenByName($name->asString());

                if ($token !== null) {
                    $currentTimestamp = time();
                    if ($currentTimestamp - $token->getCreated() < 3600) {
                        continue;
                    }
                    $token->setDropPercent($percent);
                    $token->setCreated($currentTimestamp);
                    $token->setData();
                } else {

                    $url = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(2)'))
                        ->findElement(WebDriverBy::tagName('a'))
                        ->getAttribute('href');
                    $url = Url::fromString($url);

                    $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                        ->getText();
                    $price = str_replace('$', '', $price);
                    $price = Price::fromFloat((float)$price);
                    $currentTimestamp = time();
                    $address = Address::fromString('');
                    $chain = Chain::fromString('');
                    $token = Factory::createBscToken(
                        $name,
                        $price,
                        $percent,
                        $url,
                        $address,
                        $currentTimestamp,
                        $chain,
                        false
                    );
                }
                $this->currentScrappedTokens[] = $token;
            } catch
            (Exception $e) {
                echo 'Error when crawl information about Token ' . $e->getMessage() . PHP_EOL;
                continue;
            }
        }
        echo 'Finish creating tokens from content ' . date('H:i:s', time()) . PHP_EOL;
    }

    private function assignChainAndAddress(): void
    {
        echo 'Start assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;

        foreach ($this->currentScrappedTokens as $token) {
            try {
                assert($token instanceof Token);
                if ($token->isComplete()) {
                    continue;
                }
                $this->client->get($token->getUrl()->asString());
                $this->client->refreshCrawler();

                $chain = $this->client->getCrawler()
                    ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                    ->getAttribute('data-chain-id');
                if ($chain !== '56') {
                    continue;
                }
                $chain = Chain::fromString('bsc');

                $address = $this->client->getCrawler()
                    ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                    ->getAttribute('data-address');

                if ($address === null) {
                    continue;
                }
                $address = Address::fromString($address);
                $token->setAddress($address);
                $token->setChain($chain);
                $token->setData();
                $token->setPoocoinAddress($address);
                $token->setData();

            } catch
            (Exception $exception) {
                continue;
            }
        }

        echo 'Finish assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;
    }

    public
    function getClient(): PantherClient
    {
        return $this->client;
    }

    private
    function startClient(): void
    {
        echo "Start crawling " . date("F j, Y, H:i:s ") . PHP_EOL;
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get(self::URL);
    }

    public
    function getCurrentScrappedTokens(): array
    {
        return $this->currentScrappedTokens;
    }

}