<?php

namespace App\GameEngine\Progression;

use App\Enum\SkillConditionKind;

/**
 * La condition d'un passif : ce qu'elle exige, et ce qu'elle vaut (ARC-12).
 *
 * `LeverGrant` porte une condition depuis ARC-03a, en annoncant que *ce qu'elle
 * vaut est ARC-12* — jusqu'ici c'etait « une chaine que rien n'interprete ».
 * Cette classe lui donne un **vocabulaire ferme** et un multiplicateur.
 *
 * ## La grammaire
 *
 * | Forme | Nature | Exemple |
 * |---|---|---|
 * | `weapon:<famille>` | build | `weapon:dagger` — *+10 % a la dague* |
 * | `armor:<ligne>` | build | `armor:plate` — *−8 % de degats subis en plaque* |
 * | `shield` | build | un bouclier au bras |
 * | `offhand_free` | build | main gauche vide |
 * | `dual_wield` | build | une arme dans chaque main |
 * | *(liste fermee)* | combat | `target_marked`, `took_hit_last_turn`… |
 *
 * **La condition porte sur une famille, jamais sur une piece nommee ni sur une
 * rarete** (garde-fou 4 du § 4.3) : un passif indexe sur un objet precis
 * vieillit avec lui, et un passif indexe sur la rarete transforme l'arbre en
 * prime au butin.
 *
 * ## Le multiplicateur, et la correction qui compte
 *
 * Le § 4.3 donne x1,4 au build et x2,0 au combat, *parce que la condition de
 * combat peut manquer*. Le § 9 bis a mesure que ce n'est pas toujours vrai :
 *
 * > **Le multiplicateur suit la frequence mesuree, pas la famille.** « Vous
 * > avez encaisse au tour precedent » est vraie des le tour 2 pour qui se bat
 * > au contact — la payer x2,0 serait offrir 43 % de puissance a un arbre.
 *
 * D'ou deux listes de conditions de combat : celles qui sont **frequentes**
 * (vraies plus des deux tiers du temps, payees x1,4 comme un build) et celles
 * qui peuvent reellement manquer (x2,0). C'est le simulateur d'ARC-17 qui
 * tranchera les frequences ; ce qui est fige ici, c'est **la regle**, et le
 * fait qu'une condition doive declarer de quel cote elle tombe.
 */
final class SkillCondition
{
    public const WEAPON_PREFIX = 'weapon';
    public const ARMOR_PREFIX = 'armor';

    /** Conditions de build sans sujet — elles se suffisent. */
    public const SHIELD = 'shield';
    public const OFFHAND_FREE = 'offhand_free';
    public const DUAL_WIELD = 'dual_wield';

    /**
     * Conditions de combat **frequentes** — vraies plus des deux tiers du
     * temps, donc payees au tarif d'une condition de build.
     *
     * `target_marked` en fait partie parce que la marque de son propre element
     * est posee des le tour 1 par un accord **gratuit** (§ 1.1) : c'est
     * exactement l'ecart n° 11 que le canon a tranche pour les capstones.
     *
     * @var list<string>
     */
    public const FREQUENT_COMBAT = [
        'target_marked',
        'took_hit_last_turn',
        'in_melee_range',
    ];

    /**
     * Conditions de combat qui peuvent reellement manquer — x2,0.
     *
     * @var list<string>
     */
    public const RARE_COMBAT = [
        'below_half_life',
        'target_below_quarter_life',
        'first_turn',
        'alone_in_fight',
        'full_life',
        // ARC-08d — *le combat dure*. Ajoutee pour le Gardien, et elle merite
        // sa place dans la colonne x2,0 : elle est **fausse dans toutes les
        // rencontres de trois tours**, c'est-a-dire dans le tout-venant que le
        // § 6.4 range en bande 3-5. C'est la contrepartie exacte du cout
        // structurel de l'entretien — *la fonction dont la promesse est la
        // duree est celle dont le sommet se paie en duree*.
        'long_fight',
    ];

    private function __construct(
        public readonly string $raw,
        public readonly SkillConditionKind $kind,
        public readonly ?string $subject,
        private readonly float $multiplier,
    ) {
    }

    /**
     * Lit une condition, ou refuse.
     *
     * Refuser a la lecture est la meme discipline que `CombatLever` (ARC-03a) :
     * une condition inconnue ne se corrige pas en silence, sinon un passif
     * conditionne a une chaine mal orthographiee serait **toujours inactif** —
     * et un bonus silencieusement mort est le pire des defauts, parce qu'il se
     * lit comme un choix de build.
     *
     * @throws CombatLeverDefinitionException
     */
    public static function parse(string $raw): self
    {
        $raw = trim($raw);
        if ('' === $raw) {
            throw new CombatLeverDefinitionException('An empty condition is not a condition.');
        }

        if (str_contains($raw, ':')) {
            [$prefix, $subject] = explode(':', $raw, 2);
            if ('' === trim($subject)) {
                throw new CombatLeverDefinitionException(sprintf('"%s" names a family without naming which one.', $raw));
            }

            if (self::WEAPON_PREFIX === $prefix || self::ARMOR_PREFIX === $prefix) {
                return new self($raw, SkillConditionKind::Build, trim($subject), SkillConditionKind::Build->defaultMultiplier());
            }

            throw new CombatLeverDefinitionException(sprintf('"%s" is not a condition prefix. Only "%s" and "%s" take a family.', $prefix, self::WEAPON_PREFIX, self::ARMOR_PREFIX));
        }

        if (\in_array($raw, [self::SHIELD, self::OFFHAND_FREE, self::DUAL_WIELD], true)) {
            return new self($raw, SkillConditionKind::Build, null, SkillConditionKind::Build->defaultMultiplier());
        }

        if (\in_array($raw, self::FREQUENT_COMBAT, true)) {
            // La correction du § 9 bis : frequente, donc payee comme un build.
            return new self($raw, SkillConditionKind::Combat, null, SkillConditionKind::Build->defaultMultiplier());
        }

        if (\in_array($raw, self::RARE_COMBAT, true)) {
            return new self($raw, SkillConditionKind::Combat, null, SkillConditionKind::Combat->defaultMultiplier());
        }

        throw new CombatLeverDefinitionException(sprintf('"%s" is not a known condition. A condition that nothing recognises would leave its passive silently dead.', $raw));
    }

    /**
     * Le multiplicateur d'effet de cette condition.
     *
     * Un passif sans condition vaut x1,0 : c'est `multiplierFor(null)`.
     */
    public function multiplier(): float
    {
        return $this->multiplier;
    }

    /**
     * Le multiplicateur d'une condition eventuelle — le point d'entree unique.
     */
    public static function multiplierFor(?string $raw): float
    {
        return null === $raw ? 1.0 : self::parse($raw)->multiplier();
    }

    /**
     * Cette condition se remplit-elle a l'inventaire ?
     */
    public function isBuild(): bool
    {
        return SkillConditionKind::Build === $this->kind;
    }

    /**
     * Toutes les conditions de combat connues, frequentes et rares.
     *
     * @return list<string>
     */
    public static function combatConditions(): array
    {
        return array_merge(self::FREQUENT_COMBAT, self::RARE_COMBAT);
    }
}
