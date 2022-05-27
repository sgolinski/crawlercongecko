<?php

namespace CrawlerCoinGecko;

use ArrayIterator;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;
    private array $returnArray;
    public string $linksForCoinGecko;

    public function getReturnArray(): array
    {
        return $this->returnArray;
    }

    public function __construct()
    {
        $this->client = PantherClientSingleton::getChromeClient();
        $this->returnArray = [];
        $this->linksForCoinGecko = '';
    }

    public function invoke()
    {

        try {
            $this->client->start();
            $this->client->get('https://www.coingecko.com/en/crypto-gainers-losers?time=h1');
            sleep(1);
            $content = $this->getContent();
            sleep(1);
            $this->assignElementsFromContent($content);
            $this->assignDetailInformationToCoin();
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
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
            assert($webElement instanceof RemoteWebElement);
            $name = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(1)'))
                ->findElement(WebDriverBy::tagName('div'))
                ->findElement(WebDriverBy::cssSelector('div:nth-child(1)'))->getText();
            $link = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(2)'))
                ->findElement(WebDriverBy::tagName('a'))
                ->getAttribute('href');
            $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
                ->getText();
            $percent = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
                ->getText();

            $percent = (float)$percent;

            if ($percent < -25) {
                $this->returnArray[] = new Token($name, $price, $percent, $link);
            }
        }
    }

    private function assignDetailInformationToCoin()
    {
        foreach ($this->returnArray as $token) {

            assert($token instanceof Token);
            $address = '';
            $dataChainID = '';
            $this->client->get($token->getCoingeckoLink());
            $this->client->refreshCrawler();

            if ($address == '') {
                $address = $this->client->getCrawler()
                    ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                    ->getAttribute('data-address');
            }
            if ($dataChainID == '') {
                $chainID = $this->client->getCrawler()
                    ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                    ->getAttribute('data-chain-id');
            }

            if ($address != '' && $chainID == '56') {
                $token->setMainet('bsc');
                $token->setAddress($address);
            }
        }
    }

    public function getClient(): PantherClient
    {
        return $this->client;
    }

}