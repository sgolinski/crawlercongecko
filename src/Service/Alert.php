<?php

namespace CrawlerCoinGecko\Service;

use CrawlerCoinGecko\Factory;
use CrawlerCoinGecko\Entity\Token;
use Maknz\Slack\Client as SlackClient;


class Alert
{
    private SlackClient $slack;

    private const HOOK = 'https://hooks.slack.com/services/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';

    public function __construct()
    {
        $this->slack = Factory::createSlackClient(self::HOOK);
    }

    public function sendMessageWhenIsBsc(
        array $currentRound
    ): void
    {
        foreach ($currentRound as $coin) {
            assert($coin instanceof Token);
            if ($coin->getChain()->asString() === 'bsc') {
                $message = Factory::createSlackMessage()->setText($coin->alert());
                $this->slack->sendMessage($message);
            }
        }
    }

}