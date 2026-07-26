<?php

namespace App\GameEngine\Crafting;

use App\Entity\Game\Skill;
use App\GameEngine\Player\PlayerActionHelper;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Quelles recettes sont revendiquees par un arbre de talent ? (ECO-20)
 *
 * Filet de securite du gardien « plan appris ». Une recette qu'aucun skill ne
 * cite ne doit **jamais** devenir inatteignable du seul fait qu'on a branche le
 * gardien : sans ce catalogue, ajouter une recette sans penser a l'accrocher a
 * un nœud la rendrait silencieusement impossible a fabriquer.
 *
 * La couverture est aujourd'hui totale — 82 recettes citees pour 82 existantes,
 * reconciliees par ECO-18 et ECO-19 — mais c'est une propriete des donnees, pas
 * une garantie du code.
 */
class RecipeUnlockCatalog
{
    /** @var array<string, true>|null */
    private ?array $gatedSlugs = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isGatedBySkill(string $recipeSlug): bool
    {
        return isset($this->all()[$recipeSlug]);
    }

    /**
     * @return array<string, true>
     */
    public function all(): array
    {
        if (null !== $this->gatedSlugs) {
            return $this->gatedSlugs;
        }

        $this->gatedSlugs = [];

        foreach ($this->entityManager->getRepository(Skill::class)->findAll() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (!\is_array($action) || PlayerActionHelper::CRAFT !== ($action['action'] ?? null)) {
                    continue;
                }

                foreach ((array) ($action['recipes'] ?? []) as $slug) {
                    if (\is_string($slug) && '' !== $slug) {
                        $this->gatedSlugs[$slug] = true;
                    }
                }
            }
        }

        return $this->gatedSlugs;
    }
}
