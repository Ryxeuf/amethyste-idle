<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightStatusEffect;
use App\Entity\App\Player;
use App\Entity\Game\StatusEffect;
use App\Enum\CombatLever;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverScale;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qu'une posture deplace, lu et refuse a la lecture (ARC-18b).
 *
 * `StatusEffect::levers` est une colonne JSON : elle accepte n'importe quoi. La
 * lire ici plutot que partout ou l'on en a besoin donne au vocabulaire ferme un
 * **point de passage unique**, la meme discipline que `SkillLeverReader` pour
 * les nœuds — et pour la meme raison : *le vocabulaire n'est ferme que s'il
 * existe un endroit qui refuse*.
 *
 * Quatre refus, et chacun ferme une facon de sortir du budget :
 *
 * - un levier hors de `CombatLever` — le vocabulaire cesserait d'etre ferme ;
 * - **deux entrees sur le meme levier** — la seconde effacerait la premiere en
 *   silence, et le total afficherait une ligne la ou l'auteur en a ecrit deux ;
 * - un investissement **nul** — une ligne qui ne fait rien est une intention
 *   qu'on croit avoir exprimee ;
 * - **une somme strictement positive** : la posture deplace le budget, elle ne
 *   l'ajoute pas (`StanceLaw::isBalanced()`). C'est le refus qui compte, celui
 *   qui empeche la forme de devenir un bouton qu'on presse au tour 1 et qu'on
 *   n'a plus jamais a considerer.
 *
 * Le plafond par levier, lui, **n'est pas verifie ici** et ce n'est pas un
 * oubli : un plafond borne ce qu'**un arbre** depense sur toute une vie de
 * personnage, quand une posture est un deplacement qui dure une rencontre et
 * qui rend ailleurs ce qu'il prend. Ce qui la borne est sa masse — combien elle
 * deplace d'un coup —, et c'est `MAX_WEIGHT`.
 */
class StanceLeverReader
{
    /**
     * Ce qu'une posture a le droit de deplacer, tous leviers confondus.
     *
     * La grille des nœuds va de 3 a 14 points de budget (§ 6.3), le capstone
     * — le nœud le plus lourd du jeu — valant 14. Une posture se cale sur ce
     * sommet : **elle peut valoir un capstone, jamais davantage**. Au-dela,
     * elle ne serait plus une inflexion de la rencontre mais un autre
     * personnage, et le canon a un mot pour cela — c'est la fourche, et elle se
     * decide une fois.
     */
    public const MAX_WEIGHT = 14;

    public function __construct(
        private readonly CombatLeverScale $scale,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Ce que la posture tenue par ce joueur deplace, en points de budget.
     *
     * **Ce lecteur va chercher la posture lui-meme, et c'est structurel plutot
     * que pratique.** Passer par `StatusEffectManager` aurait ete le chemin
     * evident, et il ferme un cycle : le gestionnaire de statuts lit les
     * statistiques effectives du joueur, qui lisent ses leviers, qui liraient
     * sa posture. La lecture d'une posture n'a besoin de rien de ce que le
     * gestionnaire sait faire — ni jet de chance, ni duree, ni depot — juste
     * des lignes du combat en cours ; *un lecteur qui n'emprunte que ce dont il
     * a besoin ne cree pas de cycle*.
     *
     * @return array<string, int>
     */
    public function heldBy(Player $player, ?Fight $fight): array
    {
        if ($fight === null) {
            return [];
        }

        $rows = $this->entityManager->getRepository(FightStatusEffect::class)->findBy([
            'fight' => $fight,
            'targetType' => FightStatusEffect::TARGET_TYPE_PLAYER,
            'targetId' => $player->getId(),
        ]);

        $points = [];
        foreach ($rows as $row) {
            if ($row->isExpired()) {
                continue;
            }

            foreach ($this->pointsOf($row->getStatusEffect()) as $lever => $value) {
                $points[$lever] = ($points[$lever] ?? 0) + $value;
            }
        }

        return $points;
    }

    /**
     * Les points de budget d'une posture, par levier.
     *
     * Rend `[]` pour tout statut qui n'est pas une posture : *ce qui n'est pas
     * une posture n'en a pas les pouvoirs*, et la question ne se pose donc
     * jamais aux quatorze autres statuts livres.
     *
     * @return array<string, int>
     */
    public function pointsOf(StatusEffect $effect): array
    {
        if (!StanceLaw::isStance($effect)) {
            return [];
        }

        $source = sprintf('stance "%s"', $effect->getSlug());
        $points = [];

        foreach ($effect->getLevers() as $name => $value) {
            $lever = CombatLever::tryFrom((string) $name);
            if ($lever === null) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" is not a combat lever. A stance moves the closed vocabulary, nothing else.', $source, (string) $name));
            }

            if (isset($points[$lever->value])) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" appears twice. A stance says once what it moves.', $source, $lever->value));
            }

            if (!\is_int($value) || $value === 0) {
                throw new CombatLeverDefinitionException(sprintf('%s: "%s" moves nothing. A line that does nothing is an intent one believes one expressed.', $source, $lever->value));
            }

            $points[$lever->value] = $value;
        }

        if ($points === []) {
            return [];
        }

        if (!StanceLaw::isBalanced($points)) {
            throw new CombatLeverDefinitionException(sprintf('%s: a stance moves the budget, it never adds to it — %d points are taken without being given back anywhere.', $source, array_sum($points)));
        }

        $weight = StanceLaw::weightOf($points);
        if ($weight > self::MAX_WEIGHT) {
            throw new CombatLeverDefinitionException(sprintf('%s: it moves %d budget points for a ceiling of %d. A stance is worth a capstone at most; beyond that it is another character.', $source, $weight, self::MAX_WEIGHT));
        }

        return $points;
    }

    /**
     * Ce que la posture donne **par levier**, en effet et non en points.
     *
     * Sert l'ecran : le joueur choisit entre deux postures, il lit donc des
     * pourcentages. Le budget, lui, continue de se compter en points — les
     * confondre ferait acheter la puissance deux fois (§ 4.3).
     *
     * @return array<string, float>
     */
    public function effectsOf(StatusEffect $effect): array
    {
        $effects = [];
        foreach ($this->pointsOf($effect) as $name => $points) {
            $effects[$name] = $this->scale->effectOf(CombatLever::from($name), $points);
        }

        return $effects;
    }
}
