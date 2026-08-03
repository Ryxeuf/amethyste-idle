<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un contrat de contrebande des Ruelles (FAC-08).
 *
 * GAME_WORLD § 12.4 d : « livrer discretement une cargaison de nuit, moins de
 * capacite, pas d'escorte — un systeme propre a la Confrerie, pas un derive
 * des caravanes. » La cargaison vit DANS le contrat, jamais dans l'inventaire :
 * la confiscation aux portes d'un Bastion prend le ballot, jamais le sac —
 * la borne du canon est tenue par construction.
 */
#[ORM\Entity]
#[ORM\Table(name: 'smuggling_contract')]
#[ORM\Index(name: 'idx_smuggling_player_week', columns: ['player_id', 'week_key'])]
class SmugglingContract
{
    use TimestampableEntity;

    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CONFISCATED = 'confiscated';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /**
     * La semaine du contrat (cle ISO, WeekKey) : le plafond hebdomadaire se
     * compte sur cette colonne — pas de fenetre glissante, pas de cron.
     */
    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    private string $status = self::STATUS_IN_TRANSIT;

    /**
     * Ce que dit l'etiquette du ballot — de la fiction, jamais un objet : la
     * cargaison n'existe que par le contrat.
     */
    #[ORM\Column(name: 'cargo_label', type: 'string', length: 120)]
    private string $cargoLabel;

    #[ORM\Column(name: 'origin_zone_slug', type: 'string', length: 64)]
    private string $originZoneSlug;

    #[ORM\Column(name: 'destination_zone_slug', type: 'string', length: 64)]
    private string $destinationZoneSlug;

    /**
     * La prime, figee a l'acceptation — la preuve de ce qui a ete promis,
     * versee dans la bourse a la livraison (jamais un solde).
     */
    #[ORM\Column(name: 'reward_gils', type: 'integer')]
    private int $rewardGils;

    public function getId(): int
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

    public function getWeekKey(): string
    {
        return $this->weekKey;
    }

    public function setWeekKey(string $weekKey): self
    {
        $this->weekKey = $weekKey;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function isInTransit(): bool
    {
        return self::STATUS_IN_TRANSIT === $this->status;
    }

    public function getCargoLabel(): string
    {
        return $this->cargoLabel;
    }

    public function setCargoLabel(string $cargoLabel): self
    {
        $this->cargoLabel = $cargoLabel;

        return $this;
    }

    public function getOriginZoneSlug(): string
    {
        return $this->originZoneSlug;
    }

    public function setOriginZoneSlug(string $originZoneSlug): self
    {
        $this->originZoneSlug = $originZoneSlug;

        return $this;
    }

    public function getDestinationZoneSlug(): string
    {
        return $this->destinationZoneSlug;
    }

    public function setDestinationZoneSlug(string $destinationZoneSlug): self
    {
        $this->destinationZoneSlug = $destinationZoneSlug;

        return $this;
    }

    public function getRewardGils(): int
    {
        return $this->rewardGils;
    }

    public function setRewardGils(int $rewardGils): self
    {
        $this->rewardGils = $rewardGils;

        return $this;
    }
}
