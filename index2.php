<?php

use CrawlerCoinGecko\Token;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Maknz\Slack\Client as Slack;
use Maknz\Slack\Message;
use Symfony\Component\Panther\Client as PantherClient;

require_once __DIR__ . '/vendor/autoload.php';

header("Content-Type: text/plain");

$hookUrl = 'https://hooks.slack.com/services/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';

$slack = new Slack($hookUrl);

$returnArr = [];

try {
    $lastRoundCoins = file_get_contents('last_rounded_coins.txt');
} catch (Exception $exception) {
    echo 'File is empty ' . $exception->getMessage() . PHP_EOL;
}


try {
    $client = PantherClient::createFirefoxClient();
    $client->start();
    $client->get('https://www.coingecko.com/de/munze/trending?time=h1');

} catch (Exception $e) {
    echo 'Error when creating a client' . $e->getMessage() . PHP_EOL;
    $client = PantherClient::createFirefoxClient();
    $client->quit();
    sleep(20);
    die();
}

try {
    $content = $client->getCrawler()
        ->filter('body > div.container > div:nth-child(5) > div:nth-child(2) > div')
        ->filter('#gecko-table-all > tbody')
        ->children()
        ->getIterator();
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
    die();
}

foreach ($content as $webElement) {

    assert($webElement instanceof RemoteWebElement);
    try {
        $name = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(1)'))
            ->findElement(WebDriverBy::tagName('div'))
            ->findElement(WebDriverBy::cssSelector('div:nth-child(1)'))->getText();

        $volume = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(2)'))
            ->getText();
        $link = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(2)'))
            ->findElement(WebDriverBy::tagName('a'))
            ->getAttribute('href');
        $price = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(3)'))
            ->getText();
        $percent = $webElement->findElement(WebDriverBy::cssSelector('td:nth-child(4)'))
            ->getText();
    } catch (Exception $e) {
        echo 'Error when crawl information' . PHP_EOL . $e->getMessage() . PHP_EOL;
        continue;
    }
    $percent = (float)$percent;
    if ($percent < -30) {
        $returnArr[] = new Token($name, $price, $percent, $link);
    }

}
$client->quit();


foreach ($returnArr as $coin) {
    try {
        $client->get($coin->getCoingeckoLink());
        $client->refreshCrawler();
    } catch (Exception $e) {
        echo 'Error when downloading information' . PHP_EOL . $e->getMessage() . PHP_EOL;
        continue;
    }
    $address = '';
    $dataChainID = '';
    try {
        if ($address == null) {

            $address = $client->getCrawler()
                ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                ->getAttribute('data-address');
        }

        if ($dataChainID == null) {

            $chainID = $client->getCrawler()
                ->filter('div.coin-link-row.tw-mb-0 > div > div > img ')
                ->getAttribute('data-chain-id');
        }

        if ($address != null && $chainID == '56') {
            $coin->setMainet('bsc');
            $coin->setAddress($address);
        }

    } catch (Exception $e) {
        echo 'Error when download address' . PHP_EOL . $e->getMessage() . PHP_EOL;
        continue;
    }
}

$client->quit();

file_put_contents('last_rounded_coins.txt', serialize($returnArr));

if (!empty($lastRoundCoins)) {
    $returnArr = removeDuplicates($returnArr, unserialize($lastRoundCoins));
}
foreach ($returnArr as $coin) {

    assert($coin instanceof Token);
    if ($coin->getAddress() != null && $coin->getMainet() == "bsc") {
        $message = new Message();
        $message->setText($coin->getDescription());
        $slack->sendMessage($message);
    }
}
sleep(30);
echo 'Downloading information about large movers from last hour ' . date("F j, Y, g:i a") . PHP_EOL;

function removeDuplicates($arr1, $arr2)
{

    $uniqueArray = [];
    $notUnique = false;
    if (!empty($arr2)) {
        foreach ($arr1 as $coin) {
            $notUnique = false;
            foreach ($arr2 as $coin2) {
                if ($coin->getName() == $coin2->getName()) {
                    $notUnique = true;
                }
            }
            if (!$notUnique) {
                $uniqueArray[] = $coin;
            }
        }

        return $uniqueArray;
    } else {

        return $arr1;
    }
}
