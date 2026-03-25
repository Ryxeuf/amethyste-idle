<?php

namespace App\Entity\Game;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'game_equipment_set_bonuses')]
#[ORM\UniqueConstraint(name: 'uniq_set_pieces', columns: ['equipment_set_id', 'required_pieces'])]
class EquipmentSetBonus
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EquipmentSet::class, inversedBy: 'bonuses')]
    #[ORM\JoinColumn(name: 'equipment_set_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private EquipmentSet $equipmentSet;

    #[ORM\Column(name: 'required_pieces', type: 'integer')]
    private int $requiredPieces;

    #[ORM\Column(name: 'bonus_type', type: 'string', length: 50)]
    private string $bonusType;

    #[ORM\Column(name: 'bonus_value', type: 'integer')]
    private int $bonusValue;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEquipmentSet(): EquipmentSet
    {
        return $this->equipmentSet;
    }

    public function setEquipmentSet(EquipmentSet $equipmentSet): void
    {
        $this->equipmentSet = $equipmentSet;
    }

    public function getRequiredPieces(): int
    {
        return $this->requiredPieces;
    }

    public function setRequiredPieces(int $requiredPieces): void
    {
        $this->requiredPieces = $requiredPieces;
    }

    public function getBonusType(): string
    {
        return $this->bonusType;
    }

    public function setBonusType(string $bonusType): void
    {
        $this->bonusType = $bonusType;
    }

    public function getBonusValue(): int
    {
        return $this->bonusValue;
    }

    public function setBonusValue(int $bonusValue): void
    {
        $this->bonusValue = $bonusValue;
    }
}
