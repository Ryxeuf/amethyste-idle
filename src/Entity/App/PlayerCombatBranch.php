<?php

namespace App\Entity\App;

use App\Entity\Game\Domain;
use Doctrine\ORM\Mapping as ORM;

/**
 * La branche qu'un joueur a choisie dans un arbre de combat (ARC-14b).
 *
 * GAME_ARCHETYPES § 6.1 bis : le palier 3 ecrit **deux branches** et une seule
 * s'apprend. C'est ce renoncement qui fait que *deux pyromanciens finis*
 * cessent d'etre identiques — et c'est aussi ce qui reconcilie les deux
 * nombres du canon, l'arbre ecrivant 18 nœuds quand le personnage en apprend
 * 15.
 *
 * **Une ligne par arbre, jamais une par personnage.** C'est la lecon de DOM-04 :
 * le modele livre pour les metiers portait une specialisation unique pour tout
 * le personnage, si bien que choisir Forgeron fermait a jamais la maitrise du
 * Tanneur — l'exclusivite *entre* arbres que la doctrine interdit. Le
 * renoncement se joue **dans** l'arbre, jamais entre eux (GAME_DOMAINS § 1) :
 * mener les vingt-quatre arbres de combat reste permis, et chacun garde sa
 * propre fourche.
 *
 * La cle est le **domaine**, et non une enumeration parallele : un arbre de
 * combat n'a pas d'equivalent de `CraftSpecialization`, et en inventer une
 * aurait cree une seconde table des vingt-quatre arbres a tenir a jour.
 */
#[ORM\Entity]
#[ORM\Table(name: 'player_combat_branch')]
#[ORM\UniqueConstraint(name: 'uniq_player_combat_branch', columns: ['player_id', 'domain_id'])]
class PlayerCombatBranch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'combatBranches')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Domain::class)]
    #[ORM\JoinColumn(name: 'domain_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Domain $domain;

    /**
     * La cle de branche, telle que `combat_branches.yaml` la nomme.
     *
     * Une chaine plutot qu'une enumeration : les branches vivent en
     * configuration (ARC-14a), et les figer dans le code obligerait a toucher
     * au moteur pour ouvrir la fourche d'un vingt-cinquieme arbre.
     */
    #[ORM\Column(name: 'branch', type: 'string', length: 40)]
    private string $branch;

    #[ORM\Column(name: 'chosen_at', type: 'datetime')]
    private \DateTimeInterface $chosenAt;

    public function __construct(Player $player, Domain $domain, string $branch)
    {
        $this->player = $player;
        $this->domain = $domain;
        $this->branch = $branch;
        $this->chosenAt = new \DateTime();
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

    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * Changer de branche — le respec, qui se paie (DOM-04).
     *
     * La date repart : c'est elle qui dit depuis quand le personnage est ce
     * qu'il est, et un renoncement qu'on peut defaire sans trace n'en est pas
     * un.
     */
    public function switchTo(string $branch): void
    {
        $this->branch = $branch;
        $this->chosenAt = new \DateTime();
    }

    public function getChosenAt(): \DateTimeInterface
    {
        return $this->chosenAt;
    }
}
