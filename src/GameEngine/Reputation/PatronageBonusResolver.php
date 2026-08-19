<?php

namespace App\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\Game\FactionReward;
use App\Enum\FactionRewardForm;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Les bonus de statistiques de la faction qu'on porte — et d'elle seule
 * (FAC-01).
 *
 * GAME_WORLD § 6.4 c : « les bonus de statistiques des paliers deviennent un
 * patronage ». Le mot **deviennent** dit ce que ce resolveur fait : les
 * recompenses `stat_bonus` existaient deja en base, une par palier et par
 * faction ; elles cessent d'etre quatre bonus cumulables pour devenir un seul,
 * celui de la faction dont on porte les couleurs.
 *
 * **Elles n'etaient appliquees nulle part.** Le systeme livre les affichait sur
 * l'ecran des factions et s'arretait la : un joueur Exalte chez les Chevaliers
 * lisait « +15 % de degats physiques » et n'en voyait jamais un seul. Le defaut
 * etait muet — un chiffre juste, une promesse vide. Le patronage le solde en
 * meme temps qu'il le borne : ce qui s'applique enfin ne s'applique que pour
 * une faction a la fois.
 *
 * **Les paliers ne s'empilent pas entre eux non plus.** A Exalte, on prend le
 * bonus d'Exalte, pas la somme d'Ami + Honore + Exalte : « un palier de
 * reputation ouvre des portes ; il n'empile jamais de la puissance ». Le plus
 * haut palier atteint remplace les precedents.
 */
class PatronageBonusResolver
{
    /**
     * Les statistiques qu'un `stat_bonus` peut nommer.
     *
     * `speed` figure dans les donnees livrees (le Pas de l'ombre des Ruelles)
     * sans qu'aucune agregation ne l'attende. Elle est listee ici pour que le
     * resolveur la rende visible plutot que de l'avaler en silence — un appelant
     * qui la lira saura qu'elle existe.
     *
     * @var list<string>
     */
    public const STATS = ['damage', 'heal', 'hit', 'critical', 'life', 'speed'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReputationManager $reputationManager,
    ) {
    }

    /**
     * Les pourcentages accordes par le patron, par statistique.
     *
     * Sans patron, tout vaut zero — et c'est la moitie du jalon : quatre
     * factions montees en parallele n'accordent plus rien tant qu'on n'a pas
     * choisi laquelle on porte.
     *
     * @return array<string, int>
     */
    public function percentsFor(Player $player): array
    {
        $percents = array_fill_keys(self::STATS, 0);

        $patron = $player->getPatronFaction();
        if ($patron === null) {
            return $percents;
        }

        $playerFaction = $this->reputationManager->getPlayerFaction($player, $patron);
        if ($playerFaction === null) {
            return $percents;
        }

        $reputation = $playerFaction->getReputation();

        $best = null;
        $bestThreshold = null;
        foreach ($this->entityManager->getRepository(FactionReward::class)->findBy([
            'faction' => $patron,
            // FAC-09 : la forme s'appelle desormais **patronage**, et c'est le
            // seul renommage du jalon qui change quelque chose — `stat_bonus`
            // decrivait ce que la recompense contient, `patronage` dit a quelle
            // condition elle parle. La seule forme du jeu qui puisse nommer une
            // statistique porte donc le nom de la regle qui la borne.
            'rewardType' => FactionRewardForm::Patronage->value,
        ]) as $reward) {
            $threshold = $reward->getRequiredTier()->threshold();
            if ($reputation < $threshold) {
                continue;
            }
            if ($bestThreshold === null || $threshold > $bestThreshold) {
                $best = $reward;
                $bestThreshold = $threshold;
            }
        }

        if ($best === null) {
            return $percents;
        }

        return $this->readReward($best->getRewardData(), $percents);
    }

    /**
     * Amplifier les bonus de combat par les pourcentages du patron.
     *
     * Le pourcentage porte sur le **total des bonus** et non sur la valeur
     * brute de l'action : c'est la convention deja tenue par les bonus
     * d'equipement du moteur de combat, et deux conventions concurrentes pour
     * un meme « +15 % » seraient illisibles a l'equilibrage.
     *
     * `life` n'est pas amplifie ici : les points de vie maximum se calculent sur
     * la base du personnage, dans `PlayerEffectiveStatsCalculator`. Les
     * amplifier aux deux endroits les compterait deux fois.
     *
     * @param array{damage: int, heal: int, hit: int, critical: int, life: int} $bonuses
     *
     * @return array{damage: int, heal: int, hit: int, critical: int, life: int}
     */
    public function amplify(Player $player, array $bonuses): array
    {
        $percents = $this->percentsFor($player);

        foreach (['damage', 'heal', 'hit', 'critical'] as $stat) {
            if ($percents[$stat] > 0 && $bonuses[$stat] > 0) {
                $bonuses[$stat] += (int) round($bonuses[$stat] * $percents[$stat] / 100);
            }
        }

        return $bonuses;
    }

    /**
     * Le pourcentage de points de vie maximum accorde par le patron.
     */
    public function maxLifePercent(Player $player): int
    {
        return $this->percentsFor($player)['life'];
    }

    /**
     * Une recompense porte une statistique, parfois deux (`extra_stat`).
     *
     * @param array<string, mixed> $data
     * @param array<string, int>   $percents
     *
     * @return array<string, int>
     */
    private function readReward(array $data, array $percents): array
    {
        foreach ([['stat', 'percent'], ['extra_stat', 'extra_percent']] as [$statKey, $percentKey]) {
            $stat = $data[$statKey] ?? null;
            $percent = $data[$percentKey] ?? null;

            if (\is_string($stat) && \in_array($stat, self::STATS, true) && \is_int($percent)) {
                $percents[$stat] += $percent;
            }
        }

        return $percents;
    }
}
