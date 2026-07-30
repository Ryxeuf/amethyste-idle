<?php

namespace App\Entity\App;

use App\Repository\PlayerZoneActivityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ce qu'un joueur a fait dans une zone, compte (ONB-13).
 *
 * GAME_ONBOARDING § 4.4 amende GAME_WORLD § 13.1 : **le foyer d'attache ne se
 * choisit pas, il se gagne**. Deriver le foyer de la race revenait a demander
 * une orientation de carriere — kit, destination, chaine de quetes — avant
 * toute experience de jeu, c'est-a-dire une classe deguisee.
 *
 * Le constater demande de le mesurer, et rien ne le mesurait. Le journal du
 * joueur porte bien une trace par recolte, mais il est **plafonne a 200
 * entrees** : un compteur derive de lui perdrait exactement les joueurs les
 * plus actifs. D'ou une ligne par couple joueur/zone, qui ne s'efface pas.
 *
 * Le compteur ne sert **jamais** a ouvrir ou fermer quoi que ce soit. Il
 * enregistre une orientation deja prise ; il ne l'oriente pas.
 */
#[ORM\Entity(repositoryClass: PlayerZoneActivityRepository::class)]
#[ORM\Table(name: 'player_zone_activity')]
#[ORM\UniqueConstraint(name: 'player_zone_activity_unique', columns: ['player_id', 'zone_id'])]
class PlayerZoneActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    /**
     * Actes de travail comptes dans cette zone.
     *
     * « Travailler » n'est pas « passer » : voyager n'incremente rien. C'est ce
     * qui distingue le foyer d'attache de la derniere zone visitee — sans quoi
     * le foyer de tout le monde serait la ou l'acte I se termine.
     */
    #[ORM\Column(name: 'acts', type: 'integer', options: ['default' => 0])]
    private int $acts = 0;

    #[ORM\Column(name: 'last_act_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $lastActAt;

    public function __construct(Player $player, Zone $zone)
    {
        $this->player = $player;
        $this->zone = $zone;
        $this->lastActAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getZone(): Zone
    {
        return $this->zone;
    }

    public function getActs(): int
    {
        return $this->acts;
    }

    public function getLastActAt(): \DateTimeImmutable
    {
        return $this->lastActAt;
    }

    public function record(int $acts = 1): void
    {
        $this->acts += max(1, $acts);
        $this->lastActAt = new \DateTimeImmutable();
    }
}
