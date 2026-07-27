<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;

/**
 * Rendement par point d'energie (chantier « recompenser l'investissement »).
 *
 * Le budget d'energie est **egalitaire** : 240 points par jour pour tout le
 * monde, et jouer plus n'en donne pas plus (docs/BALANCE.md section 8). Le levier
 * de progression n'est donc pas le **nombre** d'actions mais ce que **chaque**
 * action rapporte. Tout bonus qui augmenterait le debit brut d'actions creuserait
 * l'ecart avec le joueur peu disponible, ce que la separation en trois couches
 * cherche justement a eviter.
 *
 * Les bonus sont des passifs de competence (regle absolue #9 : une competence
 * n'accorde jamais de sort actif), declares dans `Skill.actions`. Deux formes
 * coexistent dans les arbres livres et sont donc toutes deux acceptees ici :
 *
 *  - **map**, forme des arbres de combat et de materia (265 occurrences) :
 *        'actions' => ['yield' => ['gather_percent' => 10, 'chest_percent' => 5]]
 *  - **liste de descripteurs**, forme des arbres de recolte et d'artisanat
 *    (107 occurrences), ou vit naturellement le bonus de recolte :
 *        'actions' => [['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]]
 *
 * N'accepter que la map aurait rendu le bonus indeclarable precisement dans les
 * arbres de metier ou il a un sens.
 *
 * Les bonus s'additionnent sur l'ensemble des competences apprises.
 */
class ActionYieldResolver
{
    /**
     * Plafond cumule, en pourcentage, par categorie de rendement.
     *
     * Le plafond n'est pas une precaution de style : sans lui, un arbre suffisamment
     * long finit par multiplier le rendement d'une action au point de rendre le
     * plafond d'energie sans effet — le joueur assidu retrouverait par le rendement
     * le debit que le budget quotidien lui refuse.
     */
    public const MAX_BONUS_PERCENT = 100;

    public const CATEGORY_GATHER = 'gather_percent';
    public const CATEGORY_CHEST = 'chest_percent';

    /**
     * Bonus cumule, en pourcentage, d'une categorie de rendement. Toujours entre
     * 0 et MAX_BONUS_PERCENT.
     */
    public function getBonusPercent(Player $player, string $category): int
    {
        $total = 0;

        foreach ($player->getSkills() as $skill) {
            $actions = $skill->getActions();
            if (null === $actions) {
                continue;
            }

            // Un bonus negatif n'a pas de sens ici et serait le signe d'une
            // donnee fautive : on l'ignore plutot que de retirer du rendement.
            $total += max(0, $this->readMapForm($actions, $category));
            $total += max(0, $this->readListForm($actions, $category));
        }

        return min($total, self::MAX_BONUS_PERCENT);
    }

    /**
     * Forme map : `['yield' => ['gather_percent' => 10]]`.
     *
     * @param array<mixed> $actions
     */
    private function readMapForm(array $actions, string $category): int
    {
        if (!isset($actions['yield']) || !\is_array($actions['yield'])) {
            return 0;
        }

        return (int) ($actions['yield'][$category] ?? 0);
    }

    /**
     * Forme liste : `[['action' => 'yield', 'category' => 'gather_percent', 'percent' => 10]]`.
     *
     * Une meme competence peut declarer plusieurs categories, donc on additionne
     * au lieu de s'arreter au premier descripteur trouve.
     *
     * @param array<mixed> $actions
     */
    private function readListForm(array $actions, string $category): int
    {
        $total = 0;

        foreach ($actions as $descriptor) {
            if (!\is_array($descriptor)) {
                continue;
            }
            if (($descriptor['action'] ?? null) !== 'yield') {
                continue;
            }
            if (($descriptor['category'] ?? null) !== $category) {
                continue;
            }

            $total += (int) ($descriptor['percent'] ?? 0);
        }

        return $total;
    }

    /**
     * Applique le bonus a une quantite tiree.
     *
     * Arrondi **au plus proche** et non a l'inferieur : avec un arrondi bas, un
     * bonus de 10 % sur un rendement de 1 a 2 unites ne se voyait jamais, et le
     * joueur payait des points de talent pour rien.
     */
    public function applyBonus(Player $player, string $category, int $amount): int
    {
        if ($amount <= 0) {
            return $amount;
        }

        $percent = $this->getBonusPercent($player, $category);
        if (0 === $percent) {
            return $amount;
        }

        return (int) round($amount * (100 + $percent) / 100);
    }
}
