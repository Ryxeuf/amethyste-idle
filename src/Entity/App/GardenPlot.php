<?php

namespace App\Entity\App;

use App\Entity\Game\Item;
use App\Repository\GardenPlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Parcelle du jardin d'une demeure (tache 129, HOU-02).
 *
 * Le jardin est le **pilier PBBG** du housing : une production qui avance quand
 * le joueur n'est pas la, par opposition a la recolte de zone qui coute de
 * l'energie et exige sa presence.
 *
 * Une parcelle est soit vide, soit plantee. Elle n'est jamais « en cours de
 * recolte » : la maturite se deduit de `readyAt`, ce qui evite un etat de plus
 * a maintenir coherent.
 */
#[ORM\Entity(repositoryClass: GardenPlotRepository::class)]
#[ORM\Table(name: 'garden_plot')]
#[ORM\UniqueConstraint(name: 'uniq_garden_plot_position', columns: ['house_id', 'position'])]
class GardenPlot
{
    use TimestampableEntity;

    /**
     * Parcelles d'un jardin.
     *
     * Quatre : assez pour que planter soit un choix (quelle plante, laquelle
     * attend), pas assez pour couvrir la demande d'un alchimiste. Le jardin
     * complete la recolte de zone, il ne la remplace pas.
     */
    public const PLOT_COUNT = 4;

    /**
     * Duree de maturation, en secondes.
     *
     * Trois heures : plus long qu'une expedition courte, assez court pour
     * qu'une session quotidienne en voie le bout.
     */
    public const GROWTH_SECONDS = 10_800;

    /** Rendement d'une parcelle : ce qu'une graine rend a la recolte. */
    public const YIELD_MIN = 2;
    public const YIELD_MAX = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PlayerHouse::class)]
    #[ORM\JoinColumn(name: 'house_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PlayerHouse $house;

    #[ORM\Column(name: 'position', type: 'integer')]
    private int $position = 0;

    /**
     * Plante mise en terre.
     *
     * Le jeu n'a pas d'objet « graine » : on plante **la plante elle-meme**, et
     * la parcelle en rend plusieurs. Le jardin multiplie lentement ce qu'on
     * possede deja — auto-limitant, puisqu'il faut d'abord recolter dehors.
     */
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(name: 'crop_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Item $crop = null;

    #[ORM\Column(name: 'ready_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $readyAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHouse(): PlayerHouse
    {
        return $this->house;
    }

    public function setHouse(PlayerHouse $house): self
    {
        $this->house = $house;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = max(0, $position);

        return $this;
    }

    public function getCrop(): ?Item
    {
        return $this->crop;
    }

    public function getReadyAt(): ?\DateTimeImmutable
    {
        return $this->readyAt;
    }

    public function isEmpty(): bool
    {
        return null === $this->crop;
    }

    public function isRipe(?\DateTimeImmutable $now = null): bool
    {
        return null !== $this->crop
            && null !== $this->readyAt
            && $this->readyAt <= ($now ?? new \DateTimeImmutable());
    }

    public function getRemainingSeconds(?\DateTimeImmutable $now = null): int
    {
        if (null === $this->readyAt) {
            return 0;
        }

        return max(0, $this->readyAt->getTimestamp() - ($now ?? new \DateTimeImmutable())->getTimestamp());
    }

    public function plant(Item $crop, \DateTimeImmutable $readyAt): self
    {
        $this->crop = $crop;
        $this->readyAt = $readyAt;

        return $this;
    }

    public function clear(): self
    {
        $this->crop = null;
        $this->readyAt = null;

        return $this;
    }
}
