<?php

namespace App\Entity\App;

use App\Repository\CrafterReputationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Reputation d'un artisan dans un metier (ECO-08b).
 *
 * `GAME_PRINCIPLES` §4.5 : « l'artisan monetise son **service** (commission +
 * reputation), pas l'objet ». La reputation est donc ce qui se capitalise quand
 * l'objet, lui, part chez le client et n'y revient jamais.
 *
 * Elle est **par metier** et non globale : un maitre forgeron qui debute en
 * alchimie n'a aucune raison d'inspirer confiance a un client alchimiste. C'est
 * aussi ce qui distingue cette reputation du renom (`Player::renownScore`), qui
 * mesure la place du personnage dans le monde, et de la reputation de faction,
 * qui mesure sa place aupres d'un camp.
 */
#[ORM\Entity(repositoryClass: CrafterReputationRepository::class)]
#[ORM\Table(name: 'crafter_reputation')]
#[ORM\UniqueConstraint(name: 'uniq_crafter_reputation', columns: ['player_id', 'craft'])]
#[ORM\Index(name: 'idx_crafter_reputation_ranking', columns: ['craft', 'points'])]
class CrafterReputation
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'craft', type: 'string', length: 40)]
    private string $craft;

    /**
     * Commandes honorees dans ce metier.
     *
     * Compte separement des points : deux artisans a 40 points ne se valent pas
     * si l'un les a gagnes sur une commande de maitre et l'autre sur dix
     * commandes de debutant, et le client a le droit de voir la difference.
     */
    #[ORM\Column(name: 'deliveries', type: 'integer', options: ['default' => 0])]
    private int $deliveries = 0;

    #[ORM\Column(name: 'points', type: 'integer', options: ['default' => 0])]
    private int $points = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): self
    {
        $this->player = $player;

        return $this;
    }

    public function getCraft(): string
    {
        return $this->craft;
    }

    public function setCraft(string $craft): self
    {
        $this->craft = $craft;

        return $this;
    }

    public function getDeliveries(): int
    {
        return $this->deliveries;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function recordDelivery(int $points): self
    {
        ++$this->deliveries;
        $this->points += max(0, $points);

        return $this;
    }

    /**
     * Titre affiche au client.
     *
     * Les paliers sont larges a dessein : un client ne choisit pas un artisan au
     * point pres, il veut savoir s'il a affaire a un debutant ou a un maitre.
     */
    public function getTitle(): string
    {
        return match (true) {
            $this->points >= 500 => 'Maitre',
            $this->points >= 200 => 'Artisan confirme',
            $this->points >= 50 => 'Artisan',
            $this->points >= 10 => 'Apprenti',
            default => 'Novice',
        };
    }
}
