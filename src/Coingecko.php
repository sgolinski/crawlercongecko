<?php

namespace CrawlerCoinGecko;

use Exception;
use Maknz\Slack\Attachment;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;


class Coingecko
{
    private array $currentRound;

    private $lastRoundCoins;

    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

    public function __construct()
    {
        $this->slack = new SlackClient(self::HOOK);
        $this->takeCoinsFromLastRound();
    }


    public function invoke($coins): void
    {
        $this->setCurrentCoins($coins);
        if (empty($this->currentRound)) {
            die('Nothing to show' . PHP_EOL);
        }
        $this->currentRound = self::removeDuplicates($this->currentRound, $this->lastRoundCoins);
        $this->checkIfIsBscAndSendMessage();
    }

    public function checkIfIsBscAndSendMessage()
    {
        foreach ($this->currentRound as $coin) {
            assert($coin instanceof Token);
            if ($coin->mainet == 'bsc' && $coin->percent < -20) {
                $message = new Message();
                $message->setText($coin->getDescription());
                $this->slack->sendMessage($message);
            }
        }
    }

    private function takeCoinsFromLastRound(): void
    {
        try {
            $this->lastRoundCoins = unserialize(file_get_contents('last_rounded_coins.txt'));
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        if (empty($this->lastRoundCoins)) {
            $this->lastRoundCoins = [];
        }
    }

    public function setCurrentCoins(array $currentCoins)
    {
        $this->currentRound = $currentCoins;

    }

    public static function removeDuplicates($arr1, $arr2)
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

    public function sendAttachment($file)
    {
        $arr = file_get_contents('coins_from_coingecko.txt');
        $this->slack
            ->attach([
                'fallback' => 'List of coins.',
                'text' => $arr,
                'author_name' => 'coingecko',
                'author_link' => 'crawlercoingecko',
            ])->to('#allnotification')->send(date("F j, Y, g:i a"));

    }

}