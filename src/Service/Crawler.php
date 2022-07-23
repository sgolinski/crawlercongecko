<?php

namespace CrawlerCoinGecko\Service;

use ArrayIterator;
use CrawlerCoinGecko\Factory;
use CrawlerCoinGecko\Reader\FileReader;
use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;
use CrawlerCoinGecko\Writer\FileWriter;
use CrawlerCoinGecko\Entity\Token;
use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\Client as PantherClient;

class Crawler
{
    private PantherClient $client;

    private array $tokensFromLastCronjob;

    private array $tokensWithInformation = [];

    private array $tokensWithoutInformation = [];

    public array $tokensFromCurrentCronjob = [];

    public array $allTokensProcessed;

    private const URL = 'https://www.coingecko.com/en/crypto-gainers-losers?time=h1';

    public function __construct()
    {
        $this->tokensFromLastCronjob = FileReader::readTokensFromLastCronJob();
        $this->allTokensProcessed = FileReader::readTokensAlreadyProcessed();
    }

    public function invoke(): void
    {
        try {
            $this->startClient();
            $content = $this->getContent();
            $this->createTokensFromContent($content);
            $this->assignChainAndAddress();
            $this->tokensFromLastCronjob = [];
            FileWriter::writeTokensFromLastCronJob($this->tokensFromCurrentCronjob);
            FileWriter::writeTokensToListTokensAlreadyProcessed($this->allTokensProcessed);

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

                if ($percent->asFloat() > -15.0) {
                    continue;
                }

                $name = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(1)'))
                    ->findElement(WebDriverBy::tagName('div'))
                    ->findElement(WebDriverBy::cssSelector('div:nth-child(1)'))->getText();
                $name = Name::fromString($name);

                $tokenFromLastRound = $this->returnTokenIfIsFromLastCronjob($name);

                if ($tokenFromLastRound !== null) {
                    $this->tokensFromCurrentCronjob[] = $tokenFromLastRound;
                    continue;
                }

                $find = $this->returnTokenIfIsRecorded($name);

                if ($find !== null) {
                    $currentTimestamp = time();
                    $find->setDropPercent($percent);
                    $find->setCreated($currentTimestamp);
                    $this->tokensWithInformation[] = $find;
                    $this->tokensFromCurrentCronjob[] = $find;

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
                    $this->tokensWithoutInformation[] = Factory::createBscToken($name, $price, $percent, $url, $address, $currentTimestamp, $chain);

                }
            } catch
            (Exception $e) {
                echo 'Error when crawl information ' . $e->getMessage() . PHP_EOL;
                continue;
            }
        }
        echo 'Finish creating tokens from content ' . date('H:i:s', time()) . PHP_EOL;
    }

    private function assignChainAndAddress(): void
    {
        echo 'Start assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;

        foreach ($this->tokensWithoutInformation as $token) {

            try {
                assert($token instanceof Token);
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

                    $newToken = Factory::createBscToken(
                        $token->getName(),
                        $token->getPrice(),
                        $token->getPercent(),
                        $token->getUrl(),
                        $address,
                        $token->getCreated(),
                        $chain
                    );

                    $this->tokensWithInformation[] = $newToken;
                    $this->tokensFromCurrentCronjob[] = $newToken;
                    $this->allTokensProcessed[] = $newToken;
                }
            } catch (Exception $exception) {
                continue;
            }
        }
        $this->tokensWithoutInformation = [];
        echo 'Finish assigning chain and address ' . date('H:i:s', time()) . PHP_EOL;
    }

    private function returnTokenIfIsFromLastCronjob(
        Name $name
    ): ?Token
    {
        foreach ($this->tokensFromLastCronjob as $showedAlreadyToken) {
            if ($showedAlreadyToken->getName()->asString() === $name->asString()) {
                return $showedAlreadyToken;
            }
        }
        return null;
    }

    private function returnTokenIfIsRecorded(
        $name
    ): ?Token
    {
        foreach ($this->allTokensProcessed as $recordedToken) {
            assert($recordedToken instanceof Token);
            if ($recordedToken->getName()->asString() === $name->asString()) {
                return $recordedToken;
            }
        }
        return null;
    }

    public function getTokensWithInformation(): array
    {
        return $this->tokensWithInformation;
    }

    public function getClient(): PantherClient
    {
        return $this->client;
    }

    private function startClient(): void
    {
        echo "Start crawling " . date("F j, Y, 'H:i:s ") . PHP_EOL;
        $this->client = PantherClient::createChromeClient();
        $this->client->start();
        $this->client->get(self::URL);
    }

}