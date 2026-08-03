<?php

namespace App\GameEngine\Economy;

use App\Entity\App\PlayerItem;
use App\Enum\Purity;

/**
 * La valeur marchande de la bande de purete (MET-01).
 *
 * GAME_TRADES § 3.2 : la bande avait une valeur d'usage (`PurityChain` la
 * propage, `CraftOrderManager` l'exige) et **aucune valeur d'echange** — un
 * joueur qui investissait dans la qualite produisait moins d'objets pour le
 * meme argent. Ce service est **le seul endroit** qui traduit une bande en
 * prix : rachat PNJ, prix de reference de l'hotel des ventes, estimation
 * d'inventaire et valeur d'une commande passent tous par lui, sans quoi le
 * meme lot vaudrait une chose ici et une autre la.
 *
 * L'echelle vit dans `config/game/purity.yaml` (`market.band_multipliers`),
 * jamais en dur : trouble x1, clair x1,8, pur x3,5, parfait x9. Un lot sans
 * bande — tout ce qui n'est pas la ligne du cristal, et les objets d'avant
 * ECO-21 — vaut son prix de reference, inchange.
 *
 * Le multiplicateur s'applique **apres** les regles de l'appelant (taux de
 * rachat a 30 %, plancher de la Fonderie, coupe du receleur) : c'est ce qui
 * garantit le contrat du jalon — deux lots trouble et parfait de la meme
 * matiere gardent un rapport de prix de 9 exactement, a tous les guichets.
 */
class PurityPricer
{
    /**
     * Le taux de rachat commun des PNJ. Il vivait en dur dans le controleur de
     * boutique et en double dans son gabarit ; le voila declare une fois.
     */
    public const BUYBACK_RATE = 0.3;

    /**
     * @var array<string, float>|null
     */
    private ?array $multipliers = null;

    public function __construct(
        private readonly PurityDefinitionLoader $loader,
    ) {
    }

    public function multiplierFor(?Purity $band): float
    {
        if ($band === null) {
            return 1.0;
        }

        return $this->multipliers()[$band->value];
    }

    /**
     * Un prix de reference porte a la bande. L'arrondi est commercial (au plus
     * proche) : tronquer ferait perdre au clair x1,8 une part de sa promesse
     * sur les petits prix, la ou vivent toutes les matieres T1.
     */
    public function apply(int $price, ?Purity $band): int
    {
        return (int) round($price * $this->multiplierFor($band));
    }

    /**
     * La valeur d'un lot : le prix de reference de sa matiere, porte a sa
     * bande. C'est le chiffre que montrent l'inventaire, la banque et le
     * formulaire de vente de l'hotel des ventes.
     */
    public function unitValueOf(PlayerItem $lot): int
    {
        return $this->apply($lot->getGenericItem()->getPrice() ?? 0, $lot->getPurity());
    }

    /**
     * Le rachat PNJ d'un lot : 30 % du prix de reference, puis la bande.
     *
     * L'ordre compte. Multiplier d'abord puis tronquer a 30 % casserait le
     * rapport exact entre bandes (135 x 0,3 tronque = 40, soit x10 face au
     * trouble a 4) ; appliquer la bande au rachat deja arrondi le preserve.
     */
    public function buybackValueOf(PlayerItem $lot): int
    {
        $base = max(1, (int) (($lot->getGenericItem()->getPrice() ?? 0) * self::BUYBACK_RATE));

        return max(1, $this->apply($base, $lot->getPurity()));
    }

    /**
     * La valeur d'un ensemble de lots — les materiaux bloques d'une commande
     * de craft. Chaque lot est valorise a sa propre bande : une commande qui
     * exige du pur engage une valeur x3,5, et l'ecran doit le dire au
     * commanditaire comme a l'artisan.
     *
     * @param iterable<PlayerItem> $lots
     */
    public function lotsValueOf(iterable $lots): int
    {
        $total = 0;
        foreach ($lots as $lot) {
            $total += $this->unitValueOf($lot);
        }

        return $total;
    }

    /**
     * @return array<string, float>
     */
    private function multipliers(): array
    {
        if ($this->multipliers === null) {
            $this->multipliers = $this->loader->load()['market']['band_multipliers'];
        }

        return $this->multipliers;
    }
}
