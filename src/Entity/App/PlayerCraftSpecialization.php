<?php

namespace App\Entity\App;

use App\Enum\CraftSpecialization;
use Doctrine\ORM\Mapping as ORM;

/**
 * La branche terminale qu'un joueur a prise dans un arbre d'artisanat (DOM-04).
 *
 * **Une ligne par arbre**, et l'unicite est portee par le schema : `(player,
 * craft)` est unique. C'est la ou l'exclusivite vit — au sein de l'arbre, jamais
 * entre eux. Un joueur peut etre forgeron d'armes *et* alchimiste des remedes ;
 * il ne peut pas etre forgeron d'armes *et* d'armures.
 *
 * Poser l'exclusivite dans le schema plutot que dans un service est deliberé :
 * un chemin de code qui l'oublierait ne pourrait pas la violer, et le jour ou
 * une commande d'administration ecrira dans cette table, elle heritera de la
 * regle sans avoir a la connaitre.
 */
#[ORM\Entity]
#[ORM\Table(name: 'player_craft_specialization')]
#[ORM\UniqueConstraint(name: 'uniq_player_craft', columns: ['player_id', 'craft'])]
class PlayerCraftSpecialization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class, inversedBy: 'craftSpecializations')]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(name: 'craft', type: 'string', length: 20, enumType: CraftSpecialization::class)]
    private CraftSpecialization $craft;

    /**
     * La branche, telle que `config/game/craft_branches.yaml` la nomme.
     *
     * Une chaine plutot qu'un enum : ajouter un metier ne doit pas demander de
     * toucher au code, et les branches sont de la donnee de conception qui se
     * retend (GAME_DOMAINS § 9 — « les gabarits sont la loi, les fixtures sont
     * l'execution »).
     */
    #[ORM\Column(name: 'branch', type: 'string', length: 40)]
    private string $branch;

    /**
     * Quand la branche a ete prise.
     *
     * Sert au respec : le cout d'un changement se raconte mieux quand on peut
     * dire depuis quand le choix tient.
     */
    #[ORM\Column(name: 'chosen_at', type: 'datetime')]
    private \DateTimeInterface $chosenAt;

    public function __construct(Player $player, CraftSpecialization $craft, string $branch)
    {
        $this->player = $player;
        $this->craft = $craft;
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

    public function getCraft(): CraftSpecialization
    {
        return $this->craft;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }

    public function setBranch(string $branch): self
    {
        $this->branch = $branch;
        $this->chosenAt = new \DateTime();

        return $this;
    }

    public function getChosenAt(): \DateTimeInterface
    {
        return $this->chosenAt;
    }
}
