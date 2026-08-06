<?php

namespace App\GameEngine\Fight;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\Enum\SpellIntent;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverScale;

/**
 * Ce que les leviers d'un personnage valent sur une action donnee (ARC-03b).
 *
 * ARC-03a a livre le vocabulaire et le convertisseur ; il restait a les faire
 * entrer dans la formule. Cette classe est le **porteur** : un sac d'effets deja
 * convertis, qu'on promene le long du calcul et que chaque etape interroge pour
 * le seul levier qui la concerne.
 *
 * **Pourquoi un objet et pas un tableau.** Parce que la loi d'ARC-03 se lit en
 * deux temps — *un levier occupe une place et une seule* (tenu par le chargeur)
 * et *chaque levier se lit dans son unite* (tenu ici). `multiplierFor()` refuse
 * un levier qui n'est pas en pourcentage, `pointsFor()` refuse un levier qui
 * n'est pas en points : un taux de critique lu comme un multiplicateur donnerait
 * un chiffre plausible et faux, exactement le genre d'erreur qu'aucun ecran ne
 * montre.
 *
 * **Neutre par defaut.** `none()` rend un porteur vide : tout multiplicateur y
 * vaut 1,0 et tout bonus 0. C'est ce qui permet a ce jalon de traverser le
 * moteur sans changer une seule valeur de jeu tant qu'aucun nœud ne porte de
 * levier (la conversion du contenu est ARC-07 et ARC-08).
 */
final readonly class CombatLeverEffects
{
    /**
     * @param array<string, float> $effects effet par levier, dans l'unite du levier
     */
    private function __construct(
        private array $effects,
    ) {
    }

    /**
     * Aucun levier — le cas de tout le contenu livre a ce jour.
     */
    public static function none(): self
    {
        return new self([]);
    }

    /**
     * Convertit des points de budget en effets, une fois pour toute l'action.
     *
     * Le registre est celui de l'action : `thrift` et `wind` lisent la ressource
     * de leur registre (§ 2), les treize autres l'ignorent. Un levier de
     * ressource sans registre est simplement omis plutot que devine — deviner
     * voudrait dire choisir les PM par defaut, donc etre faux pour deux
     * registres sur trois, en silence.
     *
     * **L'intention borne l'autre moitie** (ARC-11b-b, § 3.1) : un levier
     * n'entre dans le porteur que si le geste exerce la place qu'il occupe.
     * C'est ici, et non dans chaque consommateur, parce qu'un porteur qui ne
     * contient que ce qui qualifie ne peut pas fuir : le jour ou un chemin neuf
     * lit `grip` sur un bouclier, il ne trouvera rien a lire.
     *
     * @param array<string, int> $budgetPoints points de budget par levier
     */
    public static function of(array $budgetPoints, CombatLeverScale $scale, ?CombatRegister $register = null, ?SpellIntent $intent = null): self
    {
        $effects = [];

        foreach ($budgetPoints as $name => $points) {
            $lever = CombatLever::tryFrom((string) $name);
            if ($lever === null || $points === 0) {
                continue;
            }

            if ($register === null && $scale->readsItsRegister($lever)) {
                continue;
            }

            if (!LeverIntentLaw::qualifies($lever, $intent)) {
                continue;
            }

            $effects[$lever->value] = $scale->effectOf($lever, $points, $register);
        }

        return new self($effects);
    }

    /**
     * Le meme porteur, retreci a ce qu'une intention qualifie (ARC-11b-b).
     *
     * **Pourquoi un second bornage.** L'intention d'un geste se lit en deux
     * temps : le degat et le soin repondent sur la seule fiche du sort, tandis
     * que la protection, l'amelioration et l'entrave demandent le **type de
     * l'effet de statut** — que le moteur ne charge qu'au moment de
     * l'appliquer. Borner une fois pour toutes en amont obligerait a charger le
     * statut a chaque geste, y compris les 194 qui n'en portent pas.
     *
     * On borne donc la ou chaque question a sa reponse : le porteur de l'action
     * est construit avec ce que le sort dit de lui-meme, et `aimedAt()` le
     * retrecit une seconde fois quand le statut a parle. Retrecir, jamais
     * elargir — un levier ecarte en amont ne peut pas revenir.
     */
    public function aimedAt(?SpellIntent $intent): self
    {
        if ($intent === null || $this->effects === []) {
            return $this;
        }

        $kept = [];
        foreach ($this->effects as $name => $effect) {
            $lever = CombatLever::from($name);
            if (LeverIntentLaw::qualifies($lever, $intent)) {
                $kept[$name] = $effect;
            }
        }

        return $kept === $this->effects ? $this : new self($kept);
    }

    public function isEmpty(): bool
    {
        return $this->effects === [];
    }

    /**
     * Le facteur multiplicatif d'un levier en pourcentage — 1,0 s'il est absent.
     *
     * `guard` et `thrift` portant un taux negatif, le facteur qu'ils rendent est
     * inferieur a 1 : c'est la meme lecture pour les onze leviers de cette
     * unite, sans cas particulier.
     */
    public function multiplierFor(CombatLever $lever, CombatLeverScale $scale, ?CombatRegister $register = null): float
    {
        $unit = $scale->unitOf($lever, $register);
        if ($unit !== 'percent') {
            throw new CombatLeverDefinitionException(sprintf('"%s" is expressed in "%s", not as a percentage: reading it as a multiplier would be plausible and wrong.', $lever->value, $unit));
        }

        return 1.0 + ($this->effects[$lever->value] ?? 0.0) / 100.0;
    }

    /**
     * Les points d'un levier additif — 0 s'il est absent.
     */
    public function pointsFor(CombatLever $lever, CombatLeverScale $scale, ?CombatRegister $register = null): float
    {
        $unit = $scale->unitOf($lever, $register);
        if ($unit !== 'point') {
            throw new CombatLeverDefinitionException(sprintf('"%s" is expressed in "%s", not in percentage points.', $lever->value, $unit));
        }

        return $this->effects[$lever->value] ?? 0.0;
    }

    /**
     * L'effet brut, dans l'unite du levier, quelle qu'elle soit.
     *
     * Reserve aux deux unites qui n'ont qu'un seul consommateur chacune
     * (`percent_of_max` pour `recovery`, `resource_per_turn` pour `wind` en
     * registre de sorts) : leur donner un lecteur type n'ajouterait rien.
     */
    public function rawFor(CombatLever $lever): float
    {
        return $this->effects[$lever->value] ?? 0.0;
    }
}
