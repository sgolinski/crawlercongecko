<?php

namespace CrawlerCoinGecko;

class Token
{
    public string $name = '';
    public string $price = '';
    public float $percent = 0.0;
    public string $mainet = '';
    public string $address = ' ';
    public string $poocoinLink = '';
    public string $coingeckoLink = '';
    public string $bscLink = '';

    public function setBscLink(string $bscLink): void
    {
        $this->bscLink = $bscLink;
    }

    public function __construct($name, $price, $percent, $link)
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = (float)$percent;
        $this->coingeckoLink = 'https://www.coingecko.com' . $link;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    public function getMainet(): string
    {
        return $this->mainet;
    }

    public function setMainet(string $mainet): void
    {
        $this->mainet = $mainet;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
        $this->setPoocoinLink('https://poocoin.app/tokens/' . $address);
        $this->setBscLink('https://bscscan.com/token/' . $address);
    }

    public function getPoocoinLink(): string
    {
        return $this->poocoinLink;
    }

    public function setPoocoinLink(string $poocoinLink): void
    {
        $this->poocoinLink = $poocoinLink;
    }

    public function getCoingeckoLink(): string
    {
        return $this->coingeckoLink;
    }

    public function getDescription(): ?string
    {

        return "Name: " . $this->getName() . PHP_EOL .
            "Drop percent: " . $this->getPercent() . "%" . PHP_EOL .
            "AlertService: " . $this->getCoingeckoLink() . PHP_EOL .
            "Poocoin:  " . $this->getPoocoinLink() . PHP_EOL;

    }
}
