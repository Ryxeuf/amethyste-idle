<?php

namespace App\GameEngine\Economy;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Recipe;
use App\Enum\Purity;
use App\Helper\InventoryHelper;

/**
 * Une chaine ne vaut pas mieux que son maillon le plus trouble (ECO-26).
 *
 * Un objet raffine herite d'une bande **derivee de ses intrants**, par le
 * maillon le plus faible. C'est la regle qui donne a la purete sa portee reelle :
 * sans elle, la bande mourait a la premiere fonte, et le savoir du prospecteur
 * ne valait que pour les matieres brutes.
 *
 * **La consequence recherchee** (GAME_WORLD § 5.5) : un equipement de fin de jeu
 * en bande haute exige une chaine haute **de bout en bout**, donc du cuivre
 * *pur* venu d'une zone de debut dont les filons sont reposes. C'est ce qui rend
 * une zone intermediaire **reposee** indispensable a la fin de jeu — le levier
 * contre le creux du milieu, cote purete, quand ECO-25 l'a pose cote quantite.
 *
 * **Les matieres fongibles ne comptent pas.** Une lanière de cuir ou une botte
 * d'herbes n'a pas de bande (`PurityScope`, ECO-21) : elle ne peut donc ni
 * tirer le resultat vers le bas, ni le sauver. Le maillon faible se cherche
 * parmi les seuls intrants qui portent une bande, et un craft qui n'en consomme
 * aucun rend un objet **sans bande** — l'etat normal de l'immense majorite de
 * l'artisanat.
 */
class PurityChain
{
    public function __construct(
        private readonly PurityScope $scope,
        private readonly InventoryHelper $inventoryHelper,
    ) {
    }

    /**
     * La bande du plus trouble des lots consommes, ou `null` si aucun n'en porte.
     *
     * @param iterable<PlayerItem> $consumed
     */
    public function weakestOf(iterable $consumed): ?Purity
    {
        $weakest = null;

        foreach ($consumed as $item) {
            if (!$this->scope->coversItem($item->getGenericItem())) {
                continue;
            }

            $weakest = $this->combine($weakest, $item->getPurity());
        }

        return $weakest;
    }

    /**
     * La plus basse des deux bandes, en ignorant les absences.
     *
     * `null` signifie « pas de bande », pas « bande nulle » : un intrant
     * fongible ne doit jamais tirer un resultat vers le bas. C'est la
     * distinction qui empeche la regle de degrader tout objet compose, puisque
     * presque toutes les recettes melangent du fongible et du cristal.
     */
    public function combine(?Purity $first, ?Purity $second): ?Purity
    {
        if ($first === null) {
            return $second;
        }
        if ($second === null) {
            return $first;
        }

        return $first->level() <= $second->level() ? $first : $second;
    }

    /**
     * Ce que la recette rendrait **maintenant**, et qui le decide.
     *
     * La regle doit se voir avant le craft, sinon elle est opaque et vecue comme
     * une punition : un joueur qui decouvre a la sortie que son lingot est
     * trouble sans savoir lequel de ses six intrants l'a decide n'apprend rien.
     * L'apercu nomme donc le **maillon faible**.
     */
    public function preview(Player $player, Recipe $recipe): ?PurityPreview
    {
        $bag = [];
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                $bag = $inventory->getItems()->toArray();
                break;
            }
        }

        $weakest = null;
        $weakLink = null;

        foreach ($recipe->getIngredients() as $ingredient) {
            $slug = (string) ($ingredient['slug'] ?? '');
            if ($slug === '' || !$this->scope->coversSlug($slug)) {
                continue;
            }

            // Les lots partent du **moins pur** : l'apercu doit regarder ceux
            // que le craft prendra reellement, pas les plus beaux du sac.
            $lots = \array_slice(
                $this->inventoryHelper->consumptionOrder($bag, $slug),
                0,
                max(1, (int) ($ingredient['quantity'] ?? 1)),
            );

            foreach ($lots as $lot) {
                $before = $weakest;
                $weakest = $this->combine($weakest, $lot->getPurity());

                if ($weakest !== $before && $weakest !== null) {
                    $weakLink = $lot->getGenericItem()->getName();
                }
            }
        }

        return $weakest === null || $weakLink === null
            ? null
            : new PurityPreview($weakest, $weakLink);
    }
}
