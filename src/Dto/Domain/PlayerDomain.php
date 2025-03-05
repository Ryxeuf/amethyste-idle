<?php

namespace App\Dto\Domain;

class PlayerDomain extends DomainModel
{
    /**
     * @var int
     */
    public $availableExperience;

    /**
     * @var int
     */
    public $totalExperience;

    /**
     * @var int
     */
    public $damage;

    /**
     * @var int
     */
    public $hit;

    /**
     * @var int
     */
    public $critical;
}
