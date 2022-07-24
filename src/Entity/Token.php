<?php

namespace CrawlerCoinGecko\Entity;

use CrawlerCoinGecko\ValueObjects\Address;
use CrawlerCoinGecko\ValueObjects\Chain;
use CrawlerCoinGecko\ValueObjects\DropPercent;
use CrawlerCoinGecko\ValueObjects\Name;
use CrawlerCoinGecko\ValueObjects\Url;

interface Token
{
    public function getName(): Name;

    public function getPercent(): DropPercent;

    public function getUrl(): Url;

    public function alert(): ?string;

    public function setDropPercent(DropPercent $dropPercent);

    public function setCreated(int $created);

    public function getCreated(): int;

    public function isComplete(): bool;

    public function setAddress(Address $address);

    public function setData(): void;

    public function setChain(Chain $chain);

    public function setPoocoinAddress(Address $address): void;
}