<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;

/**
 * Ce qu'une rencontre simulee a coute (ARC-17c-b).
 *
 * Les quatre colonnes de la table croisee du § 9 sexies, et rien d'autre : la
 * **duree**, les **PV restants**, la **ressource depensee** et — plus tard, par
 * `DailyAnchor` — l'attente convertie en minutes. Le canon precise pourquoi
 * c'est cette liste : *aucun exercice individuel ne pouvait voir le
 * desequilibre*, c'est la comparaison de ces quatre nombres entre builds qui le
 * revele.
 *
 * **`turns` vaut la borne quand la rencontre n'a pas de fin.** Un personnage qui
 * ne perce pas la barre de vie de son adversaire ne « perd » pas au bout de
 * soixante tours : il est dans un etat que le jeu n'a pas prevu, et le nommer
 * (`resolved: false`) vaut mieux que de rendre une duree qui se lirait comme une
 * mesure.
 */
final readonly class EncounterOutcome
{
    public function __construct(
        public string $buildLabel,
        public int $tier,
        public MonsterRank $rank,
        public int $turns,
        public bool $victory,
        public bool $resolved,
        public int $lifeLost,
        public int $lifeRemaining,
        public int $maxLife,
        public int $resourceSpent,
    ) {
    }

    /**
     * La part de la barre de vie que la rencontre a coutee, en pourcentage.
     *
     * C'est dans cette unite que le § 9 octies enonce son seuil — *une elite tue
     * un joueur seul (102-129 % de sa barre)* —, et un pourcentage se compare
     * entre deux builds quand des points de vie ne le font pas.
     */
    public function lifeCostShare(): float
    {
        if ($this->maxLife <= 0) {
            return 0.0;
        }

        return $this->lifeLost * 100.0 / $this->maxLife;
    }

    /**
     * Cette duree tient-elle dans la bande de son rang (§ 6.4) ?
     *
     * **La bande ne se lit que sur une victoire**, et c'est la moitie de son
     * sens : elle dit *combien de tours il faut pour venir a bout d'un
     * adversaire de ce rang*. Un personnage qui tombe en trois tours face a un
     * commun n'a pas « tenu la bande des 3-5 tours » — il est mort dedans, ce
     * qui est le contraire de ce que la regle mesure. Une rencontre non resolue
     * n'y est pas davantage : compter un combat qui n'a pas eu lieu.
     */
    public function isWithinBand(): bool
    {
        return $this->victory && EncounterAnchor::isWithinBand($this->turns, $this->rank);
    }
}
