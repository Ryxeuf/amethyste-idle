<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Un contrat honore par un joueur (FAC-05).
 *
 * Le contrat de la semaine est global ; chaque joueur peut l'honorer **une
 * fois**. La contrainte unique (contrat, joueur) est ce qui tient la regle —
 * pas un service, le meme choix de forme que le patronage.
 */
#[ORM\Entity]
#[ORM\Table(name: 'foundry_contract_fulfillment')]
#[ORM\UniqueConstraint(name: 'uq_foundry_fulfillment', columns: ['contract_id', 'player_id'])]
class FoundryContractFulfillment
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: FoundryContract::class)]
    #[ORM\JoinColumn(name: 'contract_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private FoundryContract $contract;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    public function getId(): int
    {
        return $this->id;
    }

    public function getContract(): FoundryContract
    {
        return $this->contract;
    }

    public function setContract(FoundryContract $contract): self
    {
        $this->contract = $contract;

        return $this;
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
}
