<?php

namespace App\Entity\App;

use App\GameEngine\Fight\TransferLaw;
use App\Repository\GroupDungeonMemberRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Membre d'un donjon de groupe (pivot PBBG, ZON-19).
 *
 * Instantane d'un participant fige a la formation du groupe : decouple le run
 * de la `Party` mutable. Une entree par joueur et par run.
 */
#[ORM\Entity(repositoryClass: GroupDungeonMemberRepository::class)]
#[ORM\Table(name: 'group_dungeon_member')]
#[ORM\UniqueConstraint(name: 'uniq_group_dungeon_member', columns: ['run_id', 'player_id'])]
#[ORM\Index(name: 'idx_group_dungeon_member_player', columns: ['player_id'])]
class GroupDungeonMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: GroupDungeonRun::class, inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'run_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GroupDungeonRun $run;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    /**
     * La part des degats de ses allies que ce membre prend a leur place.
     *
     * ARC-18d, la forme **transfert**. `0.0` par defaut : personne ne protege
     * tant que personne n'a joue le geste.
     *
     * **Elle vit sur le membre et non sur un `StatusEffect`**, et c'est un
     * constat plutot qu'un choix : le donjon de groupe a son propre modele de
     * combat (DON-02) — pas de `Fight`, donc pas de `FightStatusEffect` a
     * deposer. Le transfert etant *par nature* une mecanique de groupe, et le
     * donjon etant le seul groupe du jeu, il n'existe aujourd'hui aucun autre
     * endroit ou il aurait un sens.
     */
    #[ORM\Column(name: 'transfer_share', type: 'float', options: ['default' => 0])]
    private float $transferShare = 0.0;

    /**
     * Les tours de rencontre qu'il reste a ce transfert.
     *
     * La seconde des deux bornes du canon. Elle se decompte a chaque tour
     * resolu, y compris celui d'un absent : *dans un donjon semi-synchrone, une
     * duree qui n'avancerait que sur les tours de son porteur ne finirait
     * jamais*.
     */
    #[ORM\Column(name: 'transfer_turns', type: 'integer', options: ['default' => 0])]
    private int $transferTurns = 0;

    public function __construct(GroupDungeonRun $run, Player $player)
    {
        $this->run = $run;
        $this->player = $player;
    }

    public function getTransferShare(): float
    {
        return $this->transferShare;
    }

    public function getTransferTurns(): int
    {
        return $this->transferTurns;
    }

    /**
     * Poser un transfert, aux bornes du canon.
     *
     * Les deux bornes sont appliquees **ici**, a l'ecriture : un transfert
     * hors bornes ne peut pas exister en base, donc aucun lecteur n'a a s'en
     * mefier.
     */
    public function protectAllies(float $share, int $turns): void
    {
        $this->transferShare = TransferLaw::shareFor($share);
        $this->transferTurns = TransferLaw::durationFor($turns);
    }

    /**
     * Un tour de rencontre passe.
     *
     * Le transfert s'efface entierement quand sa duree tombe : garder une part
     * a duree nulle laisserait une ligne qui **ressemble** a un protecteur sans
     * en etre un, et c'est le genre d'etat qu'un lecteur finit par croire.
     */
    public function ageTransfer(): void
    {
        if ($this->transferTurns <= 0) {
            return;
        }

        --$this->transferTurns;

        if ($this->transferTurns <= 0) {
            $this->transferTurns = 0;
            $this->transferShare = 0.0;
        }
    }

    /**
     * Ce membre protege-t-il encore les siens ?
     */
    public function isProtecting(): bool
    {
        return $this->transferShare > 0.0
            && TransferLaw::stillProtects($this->player->getLife(), $this->transferTurns);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRun(): GroupDungeonRun
    {
        return $this->run;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
