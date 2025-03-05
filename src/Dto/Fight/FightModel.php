<?php

namespace App\Dto\Fight;

use App\Dto\Item\UsableItem;
use App\Dto\Mob\MobModel;
use App\Dto\Spell\SpellModel;
use Generator;

class FightModel
{
    public int $id;

    /**
     * Indique si le fight est fini : mob ou player mort
     */
    public bool $terminated = false;

    /**
     * Monstre du combat
     */
    public MobModel $mob;

    /**
     * Joueurs du combat
     * @var FightPlayer[]
     */
    public array $players;

    /**
     * Notifications suite à des actions
     * @var FightNotification[]
     */
    public array $notifications = [];

    /**
     * Prochaines actions ordonnées par priorités
     * @var TimelineItem[]
     */
    public array $timeline = [];

    /**
     * Les sorts utilisables par le joueur en combat
     * @var SpellModel[]
     */
    public array $spells = [];

    /**
     * Objets utilisables par le joueur en combat
     * @var UsableItem[]
     */
    public array $items = [];

    /**
     * Indique si le fight est fini en victoire : mob mort et player vivant
     * @var bool
     */
    public bool $victory = false;

    /**
     * Les objets de récompense de fin de combat
     * @var array
     */
    public array|Generator $loot = [];

}