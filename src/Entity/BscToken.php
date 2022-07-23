<?php

namespace CrawlerCoinGecko\Entity;

use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Price;
use CrawlerCoinGecko\ValueObjects\Url;
use CrawlerCoinMarketCap\Entity\Token;

class BscToken implements Token
{
    public Name $name;
    public Price $price;
    public DropPercent $percent;
    public ?Chain $chain;
    public ?Address $address;
    public Url $url;
    public int $created;
    public string $poocoinAddress;
    public string $bscscanAddress;

    public function __construct(
        Name        $name,
        Price       $price,
        DropPercent $percent,
        Url         $url,
        Address     $address,
        int         $created,
        Chain       $chain)
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = $percent;
        $this->url = $url;
        $this->address = null;
        $this->created = $created;
        $this->chain = null;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getPercent(): DropPercent
    {
        return $this->percent;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function alert(): ?string
    {

        return "Name: " . $this->getName()->asString() . PHP_EOL .
            "Drop percent: -" . $this->getPercent()->asFloat() . '%' . PHP_EOL .
            "Cmc: " . $this->getUrl()->asString() . PHP_EOL .
            "Poocoin:  " . $this->getPoocoinAddress() . PHP_EOL;
    }

    public function setDropPercent(DropPercent $dropPercent)
    {
        $this->percent = $dropPercent;
    }

    public function setAddress(Address $address)
    {
        $this->address = $address;
        $this->setPoocoinAddress('https://poocoin.app/tokens/' . $address->asString());
        $this->setBscscanAddress('https://bscscan.com/token/' . $address->asString());
    }

    public function setChain(Chain $chain)
    {
        $this->chain = $chain;
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

    private function setPoocoinAddress(string $url)
    {
        $this->poocoinAddress = $url;
    }

    public function getChain(): ?Chain
    {
        return $this->chain;
    }

    public function getBscscanAddress(): string
    {
        return $this->bscscanAddress;
    }

    public function setBscscanAddress(string $bscscanAddress): void
    {
        $this->bscscanAddress = $bscscanAddress;
    }
}