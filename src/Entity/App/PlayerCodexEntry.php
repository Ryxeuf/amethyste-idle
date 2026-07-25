<?php

namespace App\Entity\App;

use App\Entity\Game\CodexEntry;
use App\Repository\PlayerCodexEntryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Deblocage d'une entree de Codex par un joueur (NAR-05), horodate.
 * Unicite (joueur, entree) : un deblocage est idempotent.
 */
#[ORM\Entity(repositoryClass: PlayerCodexEntryRepository::class)]
#[ORM\Table(name: 'player_codex_entry')]
#[ORM\UniqueConstraint(name: 'uniq_player_codex_entry', columns: ['player_id', 'codex_entry_id'])]
#[ORM\Index(name: 'idx_player_codex_entry_player', columns: ['player_id'])]
class PlayerCodexEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: CodexEntry::class)]
    #[ORM\JoinColumn(name: 'codex_entry_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CodexEntry $codexEntry;

    #[ORM\Column(name: 'unlocked_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    public function __construct(Player $player, CodexEntry $codexEntry)
    {
        $this->player = $player;
        $this->codexEntry = $codexEntry;
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getCodexEntry(): CodexEntry
    {
        return $this->codexEntry;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }
}
