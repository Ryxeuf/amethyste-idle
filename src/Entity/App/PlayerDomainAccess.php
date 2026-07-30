<?php

namespace App\Entity\App;

use App\Entity\Game\Domain;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un arbre **ouvert** pour un personnage (ONB-08).
 *
 * C'est la notion que le modele n'avait pas. Jusqu'ici un domaine etait un
 * catalogue de nœuds que n'importe qui pouvait prendre des lors qu'il avait les
 * points : l'entree dans un metier n'etait un **acte** nulle part. Le parchemin
 * la rend visible, et cette table est la trace de cet acte.
 *
 * L'unicite `(player, domain)` est portee par le schema, pas par un service :
 * l'ouverture est idempotente **par construction**, et un second parchemin lu
 * ne peut pas produire une seconde ligne, quel que soit le chemin de code.
 *
 * ⚠️ Ce que cette table n'est **pas** : un verrou de contenu. GAME_ONBOARDING
 * § 6.3 le dit — *le parchemin est un cout, jamais un verrou*. Tout parchemin
 * est vendu a tout le monde, en posseder un n'en interdit aucun autre, et les
 * verbes elementaires (marcher, voyager, explorer, parler, ramasser, se battre
 * a mains nues) ne passent par aucune ligne d'ici.
 */
#[ORM\Entity]
#[ORM\Table(name: 'player_domain_access')]
#[ORM\UniqueConstraint(name: 'uniq_player_domain_access', columns: ['player_id', 'domain_id'])]
#[ORM\Index(columns: ['player_id'], name: 'idx_player_domain_access_player')]
class PlayerDomainAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'domainAccesses')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Domain $domain;

    /**
     * Quand l'arbre s'est ouvert.
     *
     * Sert a raconter : l'ouverture est un moment (ONB-09 la notifie), et une
     * date permet de la relire dans le journal plutot que de la deviner.
     */
    #[ORM\Column(name: 'opened_at', type: 'datetime')]
    private \DateTimeInterface $openedAt;

    public function __construct(Player $player, Domain $domain)
    {
        $this->player = $player;
        $this->domain = $domain;
        $this->openedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getDomain(): Domain
    {
        return $this->domain;
    }

    public function getOpenedAt(): \DateTimeInterface
    {
        return $this->openedAt;
    }
}
