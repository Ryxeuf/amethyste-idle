<?php

namespace App\Entity;

use DateTime;

interface CharacterInterface
{
    public function getId(): int;
    
    public function setLife(int $life): void;

    public function getLife(): int;

    public function getMaxLife(): int;

    public function setDiedAt(?DateTime $dateTime = null): void;

    public function isDead(): bool ;
}