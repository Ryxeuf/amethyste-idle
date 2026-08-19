<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use App\Repository\AwakeningRiteRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un rite d'eveil en cours (REP-04).
 *
 * GAME_WORLD § 12.3 : le **seul craft de materia du jeu**, et un service de la
 * ville plutot qu'un pouvoir de joueur.
 *
 * **Le rite est un contrat, pas un objet en attente.** Les lots d'amethystite
 * et les gils sont pris au lancement ; ce qui vit ici est la promesse de la
 * materia, pas la materia elle-meme. C'est le meme choix qu'a fait FAC-08 pour
 * le ballot de contrebande — *la cargaison vit dans le contrat, jamais dans
 * l'inventaire* —, et pour la meme raison : rien de ce qui n'existe pas encore
 * ne peut etre vendu, echange ou vole.
 *
 * **Le prix paye est fige sur la ligne.** Le cout se recalcule sinon a la
 * reclamation, avec un rang de foyer qui a pu changer entre-temps : un joueur
 * qui lance un rite au Sanctuaire et revient apres une retrogradation aurait
 * paye un prix qu'aucun ecran ne lui a montre.
 */
#[ORM\Entity(repositoryClass: AwakeningRiteRepository::class)]
#[ORM\Table(name: 'awakening_rite')]
#[ORM\Index(name: 'idx_awakening_rite_player', columns: ['player_id'])]
class AwakeningRite
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /** La zone ou le rite a ete lance — celle dont le foyer l'autorisait. */
    #[ORM\ManyToOne(targetEntity: Zone::class)]
    #[ORM\JoinColumn(name: 'zone_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Zone $zone;

    /** La materia promise. */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'materia_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Item $materia;

    #[ORM\Column(name: 'gils_paid', type: 'integer')]
    private int $gilsPaid;

    #[ORM\Column(name: 'ends_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $endsAt;

    /**
     * Reclame ou non.
     *
     * La ligne **reste** apres reclamation plutot que d'etre supprimee : c'est
     * la trace qu'un joueur a eveille, et le seul endroit ou le monde garde le
     * souvenir d'un rite accompli.
     */
    #[ORM\Column(name: 'claimed_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $claimedAt = null;

    public function __construct(Player $player, Zone $zone, Item $materia, int $gilsPaid, \DateTimeImmutable $endsAt)
    {
        $this->player = $player;
        $this->zone = $zone;
        $this->materia = $materia;
        $this->gilsPaid = $gilsPaid;
        $this->endsAt = $endsAt;
    }

    public function getId(): int
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

    public function getMateria(): Item
    {
        return $this->materia;
    }

    public function getGilsPaid(): int
    {
        return $this->gilsPaid;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getClaimedAt(): ?\DateTimeImmutable
    {
        return $this->claimedAt;
    }

    public function isClaimed(): bool
    {
        return $this->claimedAt !== null;
    }

    public function isReady(\DateTimeImmutable $now): bool
    {
        return !$this->isClaimed() && $now >= $this->endsAt;
    }

    public function claim(\DateTimeImmutable $now): self
    {
        $this->claimedAt = $now;

        return $this;
    }

    public function secondsRemaining(\DateTimeImmutable $now): int
    {
        return max(0, $this->endsAt->getTimestamp() - $now->getTimestamp());
    }
}
