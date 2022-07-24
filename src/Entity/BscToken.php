<?php

namespace CrawlerCoinGecko\Entity;

use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;

class BscToken implements Token
{
    public Name $name;
    public Price $price;
    public DropPercent $percent;
    public ?Chain $chain;
    public ?Address $address;
    public Url $url;
    public int $created;
    private bool $completeData;
    private string $poocoinAddress;
    private bool $processed;

    public function __construct(
        Name        $name,
        Price       $price,
        DropPercent $percent,
        Url         $url,
        Address     $address,
        int         $created,
        Chain       $chain,
        bool        $processed
    )
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = $percent;
        $this->url = $url;
        $this->address = $address;
        $this->created = $created;
        $this->chain = $chain;
        $this->completeData = false;
        $this->processed = $processed;
        $this->poocoinAddress = 'https://poocoin.app/tokens/';
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getPercent(): DropPercent
    {
        return $this->percent;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function alert(): ?string
    {
        return "Name: " . $this->getName()->asString() . PHP_EOL .
            "Drop percent: " . $this->getPercent()->asFloat() . '%' . PHP_EOL .
            "Coingecko: " . $this->getUrl()->asString() . PHP_EOL .
            "Address: " . $this->getAddress()->asString() . PHP_EOL .
            "Poocoin:  " . $this->getPoocoinAddress() . PHP_EOL .
            'Chain: ' . $this->getChain()->asString() . PHP_EOL .
            'Scrapped with Redis';
    }

    public function setDropPercent(DropPercent $dropPercent)
    {
        $this->percent = $dropPercent;
    }

    public function setCreated(int $created)
    {
        $this->created = $created;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getPoocoinAddress(): string
    {
        return $this->poocoinAddress;
    }

    public function setPoocoinAddress(Address $address): void
    {
        $this->poocoinAddress .= $this->address->asString();
    }

    public function getChain(): ?Chain
    {
        return $this->chain;
    }

    public function isComplete(): bool
    {
        return $this->completeData;
    }

    public function setData(): void
    {
        $this->completeData = true;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
    }

    public function setChain(Chain $chain)
    {
        $this->chain = $chain;
    }

    private function getAddress(): Address
    {
        return $this->address;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(): void
    {
        $this->processed = true;
    }
}