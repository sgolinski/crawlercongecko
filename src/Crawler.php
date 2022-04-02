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

    public function getReturnArray(): array
    {
        return $this->returnArray;
    }

    public function __construct()
    {
        $this->client = PantherClient::createChromeClient();
        $this->returnArray = [];
    }

    public function invoke()
    {
        try {
            $this->client->start();
            $this->client->get('https://www.coingecko.com/de/munze/trending?time=h1');
            sleep(2);
            $content = $this->getContent();
            sleep(2);
            $this->assignElementsFromContent($content);
            sleep(2);
            $this->assignDetailInformationToCoin();
            sleep(2);
        } catch (Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        } finally {
            $this->client->quit();
        }
    }

    private function getContent(): ArrayIterator
    {
        return $this->client->getCrawler()
            ->filter('body > div.container > div:nth-child(5) > div:nth-child(2) > div')
            ->filter('#gecko-table-all > tbody')
            ->children()
            ->getIterator();
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
            $this->returnArray[] = new Token($name, $price, $percent, $link);

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
                file_put_contents('coins_from_coingecko.txt', trim(strstr("/handelsplatz",$token->getCoingeckoLink())).PHP_EOL, FILE_APPEND);
            }
        }
    }

}