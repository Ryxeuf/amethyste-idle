<?php

namespace App\GameEngine\Balance;

use App\Enum\MonsterRank;
use App\GameEngine\Bestiary\MonsterStatTemplate;
use App\GameEngine\Dungeon\GroupDungeonCombatService;

/**
 * La rencontre de groupe, jouee comme le donjon la joue (ARC-17c-d).
 *
 * Le seuil du § 9 octies que ce simulateur sert est le plus politique des cinq :
 * ***un groupe sans tank ni soigneur vient a bout d'une elite de son palier***.
 * S'il tombe, un role est devenu necessaire — ce que le § 7 bis interdit, et ce
 * qui transformerait la composition d'un gout en un peage.
 *
 * ## Le modele est celui de DON-02, pas un modele a part
 *
 * Une rencontre a **PV partages** (`encounter_hp_per_member` x le nombre de
 * membres), les membres agissent **chacun leur tour**, et la rencontre **riposte
 * sur celui qui vient d'agir**. Rien de tout cela n'est reinvente ici : ce sont
 * les curseurs et la boucle de `GroupDungeonCombatService`. Un simulateur qui
 * jouerait sa propre version du donjon mesurerait un donjon qui n'existe pas.
 *
 * ## Ce que la mesure va trouver, et qu'il faut lire avant les chiffres
 *
 * **Le donjon ne connait aucun soin.** `DungeonActionResolver` ne rend qu'une
 * chose — un degat. Un guerisseur y frappe, il n'y soigne personne, et le tank
 * n'y mitige rien (la mitigation d'armure n'existe pas, ARC-19 la reclame ; la
 * riposte ne se deplace pas, elle frappe l'agissant).
 *
 * ***La composition n'existe donc pas encore dans le moteur de donjon.*** Les
 * quatre colonnes de la matrice ne different que par les barres de vie et les
 * degats des membres qu'on echange. Le seuil « aucun role n'est necessaire » est
 * par consequent tenu **par construction**, et le dire vaut mieux que de rendre
 * un vert qui se lirait comme un equilibrage reussi : *un seuil qu'aucun
 * mecanisme ne peut faire echouer ne mesure rien tant que le mecanisme n'existe
 * pas*. ARC-18 (le transfert, la riposte) et ARC-19 (l'aggro bornee) sont ce qui
 * lui donnera un sens.
 */
final class GroupEncounterSimulator
{
    /**
     * Le garde-fou d'instrument, calque sur celui du solo mais rapporte au
     * groupe : chaque membre a droit au meme nombre de tours qu'en solo.
     */
    public const MAX_ROUNDS = EncounterSimulator::MAX_TURNS;

    public function __construct(
        private readonly GroupDungeonCombatService $dungeon,
    ) {
    }

    /**
     * @param list<ReferenceCharacter> $members
     */
    public function simulate(array $members, int $tier, MonsterRank $rank, string $compositionLabel): GroupOutcome
    {
        $count = \count($members);
        if (0 === $count) {
            throw new \InvalidArgumentException('Une rencontre de groupe sans membre ne se joue pas.');
        }

        $encounterHpMax = $this->dungeon->getHpPerMember() * $count;
        $encounterHp = (float) $encounterHpMax;
        $strike = (float) MonsterStatTemplate::attackFor($tier, $rank);

        /** @var list<float> $life */
        $life = [];
        /** @var list<float> $resource */
        $resource = [];
        foreach ($members as $index => $member) {
            $life[$index] = (float) $member->maxLife;
            $resource[$index] = (float) $member->maxResource;
        }

        $rounds = 0;

        while ($encounterHp > 0.0 && $this->anyStanding($life) && $rounds < self::MAX_ROUNDS) {
            ++$rounds;

            foreach ($members as $index => $member) {
                if ($life[$index] <= 0.0) {
                    continue;
                }

                $encounterHp -= $this->actOf($member, $resource, $index);

                if ($encounterHp <= 0.0) {
                    break 2;
                }

                // La riposte frappe **celui qui vient d'agir** (DON-02). Elle
                // ne se deplace pas : le geste de menace qui le permettrait est
                // ARC-19, et il n'existe pas.
                $life[$index] -= $strike
                    * (1.0 - max(0.0, min(100.0, $member->dodgeRate)) / 100.0)
                    * max(0.0, $member->guardMultiplier);

                if ($life[$index] > 0.0) {
                    $life[$index] = min((float) $member->maxLife, $life[$index] + $member->recoveryPerTurnPoints());
                    $resource[$index] = min((float) $member->maxResource, $resource[$index] + $member->resourcePerTurn);
                }
            }
        }

        $victory = $encounterHp <= 0.0;
        $down = 0;
        foreach ($life as $remaining) {
            if ($remaining <= 0.0) {
                ++$down;
            }
        }

        return new GroupOutcome(
            compositionLabel: $compositionLabel,
            tier: $tier,
            rank: $rank,
            turns: $rounds,
            victory: $victory,
            resolved: $victory || $down === $count,
            membersDown: $down,
            memberCount: $count,
            encounterHpRemaining: max(0, (int) round($encounterHp)),
            encounterHpMax: $encounterHpMax,
        );
    }

    /**
     * Ce qu'un membre retire ce tour-ci, ressource deduite.
     *
     * Meme regle qu'en solo : un registre sans ressource joue toujours son
     * geste, et seul un pool qui existe peut se vider.
     *
     * @param list<float> $resource
     */
    private function actOf(ReferenceCharacter $member, array &$resource, int $index): float
    {
        if (!$member->spendsAResource()) {
            return $member->expectedDamagePerTurn();
        }

        if ($resource[$index] >= $member->gestureCost) {
            $resource[$index] -= $member->gestureCost;

            return $member->expectedDamagePerTurn();
        }

        return $member->expectedFallbackDamagePerTurn();
    }

    /**
     * @param list<float> $life
     */
    private function anyStanding(array $life): bool
    {
        foreach ($life as $remaining) {
            if ($remaining > 0.0) {
                return true;
            }
        }

        return false;
    }
}
