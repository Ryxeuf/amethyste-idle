<?php

namespace App\Entity\App;

use App\Repository\PlayerJournalEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: PlayerJournalEntryRepository::class)]
#[ORM\Table(name: 'player_journal_entry')]
#[ORM\Index(columns: ['player_id', 'created_at'], name: 'idx_journal_player_date')]
#[ORM\Index(columns: ['type'], name: 'idx_journal_type')]
class PlayerJournalEntry
{
    use TimestampableEntity;

    public const TYPE_COMBAT_VICTORY = 'combat_victory';
    public const TYPE_COMBAT_DEFEAT = 'combat_defeat';
    public const TYPE_QUEST_COMPLETED = 'quest_completed';
    public const TYPE_CRAFT = 'craft';
    public const TYPE_GATHERING = 'gathering';
    public const TYPE_DUNGEON = 'dungeon';
    public const TYPE_DOMAIN_LEVEL = 'domain_level';

    public const TYPES = [
        self::TYPE_COMBAT_VICTORY,
        self::TYPE_COMBAT_DEFEAT,
        self::TYPE_QUEST_COMPLETED,
        self::TYPE_CRAFT,
        self::TYPE_GATHERING,
        self::TYPE_DUNGEON,
        self::TYPE_DOMAIN_LEVEL,
    ];

    public const TYPE_LABELS = [
        self::TYPE_COMBAT_VICTORY => 'game.journal.type.combat_victory',
        self::TYPE_COMBAT_DEFEAT => 'game.journal.type.combat_defeat',
        self::TYPE_QUEST_COMPLETED => 'game.journal.type.quest_completed',
        self::TYPE_CRAFT => 'game.journal.type.craft',
        self::TYPE_GATHERING => 'game.journal.type.gathering',
        self::TYPE_DUNGEON => 'game.journal.type.dungeon',
        self::TYPE_DOMAIN_LEVEL => 'game.journal.type.domain_level',
    ];

    public const TYPE_ICONS = [
        self::TYPE_COMBAT_VICTORY => 'sword',
        self::TYPE_COMBAT_DEFEAT => 'skull',
        self::TYPE_QUEST_COMPLETED => 'scroll',
        self::TYPE_CRAFT => 'hammer',
        self::TYPE_GATHERING => 'pickaxe',
        self::TYPE_DUNGEON => 'dungeon',
        self::TYPE_DOMAIN_LEVEL => 'star',
    ];

    public const MAX_ENTRIES_PER_PLAYER = 200;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\Column(type: 'string', length: 30)]
    private string $type;

    #[ORM\Column(type: 'string', length: 255)]
    private string $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getTypeIcon(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'default';
    }
}
