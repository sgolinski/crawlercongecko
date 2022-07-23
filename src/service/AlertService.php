<?php

namespace CrawlerCoinGecko\service;

use CrawlerCoinGecko\BscToken;
use Exception;
use Maknz\Slack\Client as SlackClient;
use Maknz\Slack\Message;


class AlertService
{
    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

    public function __construct()
    {
        $this->slack = new SlackClient(self::HOOK);
    }


    public function sendMessageWhenIsBsc(array $currentRound)
    {
        foreach ($currentRound as $coin) {
            assert($coin instanceof BscToken);
            if ($coin->chain !== null) {
                $message = new Message();
                $message->setText($coin->getDescription());
                $this->slack->sendMessage($message);
            }
        }
    }


}