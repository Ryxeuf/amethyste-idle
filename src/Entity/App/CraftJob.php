<?php

namespace App\Entity\App;

use App\Entity\Game\Recipe;
use App\Repository\CraftJobRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Travail d'atelier en cours a l'etabli (ECO-20).
 *
 * `Recipe.craftingTime` etait affiche au joueur (« Temps : 12s ») sur chaque
 * carte de recette et **applique nulle part** : `craft()` consommait les
 * ingredients et creait l'objet dans la meme requete. Depuis ECO-07a, les
 * commandes de craft respectaient ce temps ; l'etabli, non. Le jeu avait deux
 * regimes de temps pour la meme action.
 *
 * Un seul travail a la fois par artisan : un etabli est un etabli. C'est aussi
 * ce qui donne son sens a la quantite — un lot de dix occupe dix fois plus
 * longtemps, au lieu de dix crafts instantanes enchaines.
 */
#[ORM\Entity(repositoryClass: CraftJobRepository::class)]
#[ORM\Table(name: 'craft_job')]
#[ORM\UniqueConstraint(name: 'uniq_craft_job_player', columns: ['player_id'])]
class CraftJob
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[ORM\JoinColumn(name: 'player_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Player $player;

    #[ORM\ManyToOne(targetEntity: Recipe::class)]
    #[ORM\JoinColumn(name: 'recipe_id', referencedColumnName: 'id', nullable: false)]
    private Recipe $recipe;

    #[ORM\Column(name: 'quantity', type: 'integer', options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(name: 'ready_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $readyAt;

    public function getId(): ?int
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

    public function getRecipe(): Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(Recipe $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    public function getReadyAt(): \DateTimeImmutable
    {
        return $this->readyAt;
    }

    public function setReadyAt(\DateTimeImmutable $readyAt): self
    {
        $this->readyAt = $readyAt;

        return $this;
    }

    public function isReady(?\DateTimeImmutable $now = null): bool
    {
        return $this->readyAt <= ($now ?? new \DateTimeImmutable());
    }

    public function getRemainingSeconds(?\DateTimeImmutable $now = null): int
    {
        return max(0, $this->readyAt->getTimestamp() - ($now ?? new \DateTimeImmutable())->getTimestamp());
    }
}
