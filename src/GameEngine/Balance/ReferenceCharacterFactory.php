<?php

namespace App\GameEngine\Balance;

use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\Enum\SpellIntent;
use App\GameEngine\Fight\BareHandsAttack;
use App\GameEngine\Fight\Calculator\CriticalCalculator;
use App\GameEngine\Fight\CombatLeverEffects;
use App\GameEngine\Progression\CombatLeverScale;
use App\Service\PlayerFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Du build a la fiche : convertir des points de budget en chiffres de combat
 * (ARC-17c-b).
 *
 * `ReferenceBuildFactory` (ARC-17c-a) derive **ce qu'une branche a appris** ;
 * cette fabrique derive **ce que ca vaut**. La frontiere est celle d'ARC-03 : les
 * points de budget ne deviennent un effet que par le convertisseur unique, et le
 * simulateur ne fait pas exception. Lui laisser sa propre table de conversion
 * aurait produit le defaut que le canon nomme partout ailleurs — ***une regle
 * recopiee derive de son original en silence***, et un simulateur qui derive de
 * la formule qu'il mesure ne mesure plus rien.
 *
 * ## Ce qui est lu, et d'ou
 *
 *  - **La barre de vie** part de `PlayerFactory::BASE_LIFE` — ce qu'un
 *    personnage porte reellement en sortant du tunnel — et le levier `life` la
 *    multiplie. Il n'y a pas d'autre source : sans niveau global (regle absolue
 *    n° 6), une barre de vie ne grandit que par l'arbre.
 *  - **Le geste** est le plus fort des accords que la branche ouvre. Un
 *    personnage joue son meilleur coup ; lui faire jouer une moyenne de ses
 *    gestes mesurerait un joueur qui n'existe pas.
 *  - **La ressource** est celle du registre (§ 2). Seuls les sorts la portent
 *    d'une rencontre a la suivante (`DailyAnchor::carriesOverBetweenEncounters`)
 *    — la melee paie en tours et le tir dans son carquois, tous deux **dans** la
 *    rencontre. Ces deux couts existent dans le jeu et **ne sont pas modelises
 *    ici** : la consequence est qu'a l'echelle d'une rencontre isolee, la melee
 *    et le tir paraissent gratuits. C'est une lecture a garder pour la journee
 *    d'ARC-17c-c, ou le partage se voit.
 *
 * ## Le repli, et l'arme qu'on prete
 *
 * Un lanceur a sec ne cesse pas de jouer : l'attaque de base reste gratuite
 * (regle absolue n° 10). Ce qu'elle vaut depend de l'arme, et c'est la
 * l'equipement que ce jalon devait poser.
 *
 * **On prete la meilleure arme du jeu, pas celle du palier** — et c'est un choix
 * de methode. Le palier d'une arme n'est pas une donnee : mesuree, la colonne
 * `level` des armes ne le porte pas (`t2-axe` y declare 5). Deriver un palier
 * d'un prefixe de slug reviendrait a inventer la donnee qui manque. Preter la
 * meilleure est la lecture **la plus favorable au personnage** : si l'ecart tient
 * avec elle, il tient a fortiori avec l'arme de son palier.
 *
 * Le prete ne change d'ailleurs pas la conclusion, et le chiffre le dit : les
 * armes livrees frappent de **1 a 3** quand un commun de palier 2 porte **70 PV**.
 * *L'equipement n'est pas ce qui separe un archetype d'un autre* — c'est
 * exactement ce que le § 0.2 annonce, verifie ici plutot que suppose.
 *
 * La mitigation d'armure, elle, ne se prete pas : elle n'existe pas dans le
 * moteur (GAME_ITEMS § 2.2 la mesure, ARC-19 la reclame). Simuler ce que le jeu
 * ne fait pas mesurerait un autre jeu.
 */
final class ReferenceCharacterFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatLeverScale $leverScale,
    ) {
    }

    public function of(ReferenceBuild $build): ReferenceCharacter
    {
        // L'intention `degat` est celle sous laquelle se lisent **et** ce qu'on
        // inflige (`power`, `critical`, `hit`) **et** ce qu'on encaisse
        // (`guard`, `dodge`) : c'est le meme coup, vu des deux cotes. Les
        // leviers hors intention (`life`, `recovery`, `wind`, `thrift`) y
        // figurent aussi, la loi ne les bornant pas (`LeverIntentLaw`).
        $effects = CombatLeverEffects::of($build->leverBudget, $this->leverScale, $build->register, SpellIntent::Damage);

        $gesture = $this->strongestGestureOf($build);

        $baseDamage = $gesture !== null ? (int) $gesture->getDamage() : 0;
        $power = $effects->multiplierFor(CombatLever::Power, $this->leverScale);

        return new ReferenceCharacter(
            label: $build->label(),
            role: $build->role,
            register: $build->register,
            maxLife: $this->maxLifeOf($effects),
            maxResource: $this->maxResourceOf($build->register),
            gestureSlug: $gesture?->getSlug() ?? '',
            gestureDamage: max(0, (int) round($baseDamage * $power)),
            gestureCost: $this->costOf($gesture, $build->register, $effects),
            fallbackDamage: max(0, (int) round($this->baseAttackDamage() * $power)),
            hitRate: $this->hitRateOf($gesture, $effects),
            criticalRate: $this->criticalRateOf($gesture, $effects),
            criticalPower: CriticalCalculator::CRITICAL_MULTIPLIER * $effects->multiplierFor(CombatLever::CriticalPower, $this->leverScale),
            guardMultiplier: $effects->multiplierFor(CombatLever::Guard, $this->leverScale),
            dodgeRate: $effects->pointsFor(CombatLever::Dodge, $this->leverScale),
            recoveryPerTurn: $effects->rawFor(CombatLever::Recovery),
            resourcePerTurn: $this->resourcePerTurnOf($build->register, $effects),
        );
    }

    /**
     * Le plus fort des gestes que la branche ouvre.
     *
     * **Les gestes en pourcentage sont ecartes**, pour la raison qui fait
     * qu'`MonsterDamageLaw` les ecarte aussi : ils se mesurent sur la vie de
     * leur cible, donc leur chiffre ne se compare pas a celui d'un geste fixe.
     * Les retenir ferait gagner l'arbre qui en porte un, quel que soit son
     * palier.
     */
    private function strongestGestureOf(ReferenceBuild $build): ?Spell
    {
        $best = null;

        foreach ($build->accords as $slug) {
            $spell = $this->spellOf($slug);
            if ($spell === null || $spell->isPercent()) {
                continue;
            }

            $damage = (int) ($spell->getDamage() ?? 0);
            if ($damage <= 0) {
                continue;
            }

            if ($best === null || $damage > (int) $best->getDamage()) {
                $best = $spell;
            }
        }

        return $best;
    }

    private function spellOf(string $slug): ?Spell
    {
        return $this->entityManager->getRepository(Spell::class)->findOneBy(['slug' => $slug]);
    }

    /**
     * L'attaque de base : la meilleure arme du jeu, ou les mains nues.
     *
     * Le repli sur les mains nues n'est pas defensif : c'est le plancher que le
     * jeu garantit (ONB-20a), et il vaut pour un personnage a qui aucune arme
     * n'est accessible. Prendre le maximum des deux revient a dire *ce que le
     * jeu permet de mieux*, ce qui est le seul repere qu'un instrument puisse
     * lire sans choisir.
     */
    private function baseAttackDamage(): int
    {
        $bareHands = $this->spellOf(BareHandsAttack::SPELL_SLUG);
        $best = $bareHands !== null ? (int) ($bareHands->getDamage() ?? 0) : 0;

        foreach ($this->entityManager->getRepository(Item::class)->findBy(['type' => Item::TYPE_GEAR_PIECE]) as $item) {
            $spell = $item->getSpell();
            if ($spell === null || $spell->isPercent()) {
                continue;
            }

            $best = max($best, (int) ($spell->getDamage() ?? 0));
        }

        return $best;
    }

    /**
     * La barre de vie : la base du jeu, multipliee par le levier `life`.
     */
    private function maxLifeOf(CombatLeverEffects $effects): int
    {
        return max(1, (int) round(PlayerFactory::BASE_LIFE * $effects->multiplierFor(CombatLever::Life, $this->leverScale)));
    }

    /**
     * Le pool de la ressource du registre — nul quand elle ne se reporte pas.
     */
    private function maxResourceOf(CombatRegister $register): int
    {
        return $register === CombatRegister::Spell ? PlayerFactory::BASE_MAX_ENERGY : 0;
    }

    /**
     * Ce que le geste coute, `thrift` applique.
     */
    private function costOf(?Spell $gesture, CombatRegister $register, CombatLeverEffects $effects): int
    {
        if ($gesture === null || $register !== CombatRegister::Spell) {
            return 0;
        }

        $thrift = $effects->multiplierFor(CombatLever::Thrift, $this->leverScale, $register);

        return max(0, (int) round($gesture->getEnergyCost() * $thrift));
    }

    private function hitRateOf(?Spell $gesture, CombatLeverEffects $effects): float
    {
        $base = $gesture?->getHit() ?? 0;

        return $base + $effects->pointsFor(CombatLever::Hit, $this->leverScale);
    }

    private function criticalRateOf(?Spell $gesture, CombatLeverEffects $effects): float
    {
        $base = $gesture?->getCritical() ?? 0;

        return $base + $effects->pointsFor(CombatLever::Critical, $this->leverScale);
    }

    /**
     * Ce que `wind` rend par tour — et seulement en registre de sorts.
     *
     * En melee et au tir, le levier rend du temps de reprise ou des munitions :
     * deux ressources **intra-rencontre** que cet instrument ne joue pas. Rendre
     * leur chiffre ici le ferait entrer dans le pool des PM, ou il n'a rien a
     * faire — un levier lu dans la mauvaise unite donne un chiffre plausible et
     * faux.
     */
    private function resourcePerTurnOf(CombatRegister $register, CombatLeverEffects $effects): float
    {
        return $register === CombatRegister::Spell ? $effects->rawFor(CombatLever::Wind) : 0.0;
    }
}
