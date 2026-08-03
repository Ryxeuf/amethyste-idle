<?php

namespace App\Entity\App;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Le contrat d'approvisionnement de la semaine (FAC-05).
 *
 * Un seul par semaine, global — la Fonderie publie, tout le monde lit la meme
 * affiche. La cle de semaine est unique : rejouer la rotation retombe sur la
 * ligne existante, jamais sur un reroll.
 *
 * `referencePrice` fige la reference du marche lue **au tirage** (mediane HV
 * des ventes conclues, ou prix d'item si le marche etait muet) : c'est la
 * preuve que le garde-fou « prix contractuel < marche » a ete verifie, et
 * elle reste lisible apres coup.
 */
#[ORM\Entity]
#[ORM\Table(name: 'foundry_contract')]
#[ORM\UniqueConstraint(name: 'uq_foundry_contract_week', columns: ['week_key'])]
class FoundryContract
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    #[ORM\Column(name: 'week_key', type: 'string', length: 10)]
    private string $weekKey;

    #[ORM\Column(name: 'item_slug', type: 'string', length: 64)]
    private string $itemSlug;

    #[ORM\Column(name: 'volume', type: 'integer')]
    private int $volume;

    #[ORM\Column(name: 'gils_per_unit', type: 'integer')]
    private int $gilsPerUnit;

    #[ORM\Column(name: 'essence', type: 'integer')]
    private int $essence;

    #[ORM\Column(name: 'reference_price', type: 'integer')]
    private int $referencePrice;

    public function getId(): int
    {
        return $this->id;
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

    public function getItemSlug(): string
    {
        return $this->itemSlug;
    }

    public function setItemSlug(string $itemSlug): self
    {
        $this->itemSlug = $itemSlug;

        return $this;
    }

    public function getVolume(): int
    {
        return $this->volume;
    }

    public function setVolume(int $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getGilsPerUnit(): int
    {
        return $this->gilsPerUnit;
    }

    public function setGilsPerUnit(int $gilsPerUnit): self
    {
        $this->gilsPerUnit = $gilsPerUnit;

        return $this;
    }

    public function getEssence(): int
    {
        return $this->essence;
    }

    public function setEssence(int $essence): self
    {
        $this->essence = $essence;

        return $this;
    }

    public function getReferencePrice(): int
    {
        return $this->referencePrice;
    }

    public function setReferencePrice(int $referencePrice): self
    {
        $this->referencePrice = $referencePrice;

        return $this;
    }

    public function getTotalGils(): int
    {
        return $this->gilsPerUnit * $this->volume;
    }
}
