<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Enum\MonsterRank;
use App\GameEngine\Fight\MonsterDamageLaw;
use App\GameEngine\Realtime\Dungeon\GroupDungeonCombatPublisher;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Boucle de combat tour par tour partagee d'un donjon de groupe (ZON-19,
 * sous-jalon 2).
 *
 * Semi-synchrone : les membres agissent chacun leur tour contre une rencontre a
 * PV partages, mais aucune presence simultanee n'est requise — un delai par
 * tour (`zone.dungeon.turn_seconds`, defaut 45 s) borne l'attente ; au-dela,
 * l'action par defaut (attaque de base de l'arme, toujours gratuite) est
 * resolue paresseusement au prochain chargement d'ecran (aucun cron). Quand la
 * rencontre tombe, le run est complete.
 *
 * Curseurs (table `parameter`) :
 *  - `zone.dungeon.turn_seconds` : delai par tour (defaut 45).
 *  - `zone.dungeon.encounter_hp_per_member` : PV de rencontre par membre (defaut 200).
 */
class GroupDungeonCombatService
{
    public const DEFAULT_TURN_SECONDS = 45;
    public const PARAM_TURN_SECONDS = 'zone.dungeon.turn_seconds';
    // DON-02 : 200 etait calibre pour une rencontre **sans riposte** — le jour
    // ou elle frappe, 800 PV pour quatre rendraient le soigneur obligatoire
    // (GAME_ARCHETYPES §7 bis). Ramene a ~120 avec l'arrivee de la riposte.
    public const DEFAULT_HP_PER_MEMBER = 120;
    public const PARAM_HP_PER_MEMBER = 'zone.dungeon.encounter_hp_per_member';
    // DON-02 : la riposte — la rencontre frappe le membre qui vient d'agir.
    public const DEFAULT_ENCOUNTER_HIT = 10;
    public const PARAM_ENCOUNTER_HIT = 'zone.dungeon.encounter_hit';

    private const MAX_AUTO_RESOLUTIONS = 100;

    /**
     * DON-03 — les trois etapes d'un donjon, du tout-venant au boss.
     * Le rang de chaque etape est fixe ; la creature qui l'incarne est tiree
     * dans la faune du palier de la zone.
     */
    public const STEP_RANKS = [MonsterRank::Common, MonsterRank::Elite, MonsterRank::Boss];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupDungeonCombatPublisher $publisher,
        private readonly GroupDungeonRewardService $rewardService,
        private readonly DungeonActionResolver $actionResolver,
        private readonly DungeonEncounterPicker $encounterPicker,
    ) {
    }

    /**
     * Etat de combat courant, apres initialisation eventuelle et resolution
     * paresseuse des tours en retard.
     *
     * @return array<string, mixed>
     */
    public function state(GroupDungeonRun $run): array
    {
        $changed = false;
        if (GroupDungeonRun::STATUS_IN_PROGRESS === $run->getStatus() && !$run->isCombatInitialized()) {
            $this->initializeCombat($run);
            $changed = true;
        }

        $changed = $this->resolveOverdueTurns($run) > 0 || $changed;
        $this->entityManager->flush();

        $snapshot = $this->snapshot($run);
        if ($changed) {
            $this->publisher->publishState($run, $snapshot);
        }

        return $snapshot;
    }

    /**
     * Action volontaire du joueur actif (attaque de base).
     *
     * @return array<string, mixed>
     *
     * @throws GroupDungeonException si ce n'est pas le tour du joueur ou si le run est termine
     */
    public function act(Player $player, GroupDungeonRun $run, ?string $spellSlug = null): array
    {
        if (GroupDungeonRun::STATUS_IN_PROGRESS === $run->getStatus() && !$run->isCombatInitialized()) {
            $this->initializeCombat($run);
        }
        $this->resolveOverdueTurns($run);

        if (GroupDungeonRun::STATUS_IN_PROGRESS !== $run->getStatus()) {
            throw new GroupDungeonException('game.zone.dungeon.error.not_active');
        }
        if ($run->getActivePlayerId() !== $player->getId()) {
            throw new GroupDungeonException('game.zone.dungeon.error.not_your_turn');
        }
        if ($player->isDead()) {
            throw new GroupDungeonException('game.zone.dungeon.error.member_down');
        }

        $this->applyAction($run, $player, $spellSlug);
        $this->entityManager->flush();

        $snapshot = $this->snapshot($run);
        $this->publisher->publishState($run, $snapshot);

        return $snapshot;
    }

    private function initializeCombat(GroupDungeonRun $run): void
    {
        $order = [];
        foreach ($run->getMemberPlayers() as $member) {
            $order[] = $member->getId();
        }
        if ([] === $order) {
            return;
        }

        $run->setTurnOrder($order);
        $run->setCurrentStep(0);
        $this->openEncounter($run);
        $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
    }

    /**
     * Ouvre la rencontre de l'etape courante (DON-03).
     *
     * Le donjon ne definit pas ses creatures : le rang vient de l'etape
     * (Common -> Elite -> Boss), la creature vient de la faune du **palier de
     * la zone** — un donjon T4 se peuple tout seul le jour ou le palier T4
     * est peuple. La barre de la rencontre est la vie du monstre multipliee
     * par la taille du groupe : chaque membre a « son monstre » a abattre,
     * et le rang se sent — une elite du meme palier porte plusieurs fois la
     * vie d'un commun (BES-02), la ou le sac de PV rendait toutes les etapes
     * identiques.
     *
     * Sans faune (palier vide, monstre supprime), les curseurs historiques
     * reprennent : un donjon ne refuse jamais de s'ouvrir pour un accident
     * de repartition.
     */
    private function openEncounter(GroupDungeonRun $run): void
    {
        $rank = self::STEP_RANKS[$run->getCurrentStep()] ?? MonsterRank::Boss;
        $monster = $this->encounterPicker->pick($this->encounterTier($run), $rank);
        $run->setEncounterMonster($monster);

        $members = max(1, \count($run->getTurnOrder()));
        $hp = null !== $monster
            ? max(1, (int) $monster->getLife()) * $members
            : $this->getHpPerMember() * $members;
        $run->setEncounterHp($hp, $hp);
    }

    /**
     * Le palier des rencontres : celui de la zone du donjon (GAME_DUNGEONS §3).
     *
     * Le plancher est T1 — une zone T0 est sure par definition (GAME_BESTIARY,
     * invariant 3), son donjon puise donc au premier palier qui a une faune.
     */
    private function encounterTier(GroupDungeonRun $run): int
    {
        $tier = $run->getDungeon()->getZone()?->getTier() ?? $run->getZone()?->getTier() ?? 1;

        return max(1, min(4, $tier));
    }

    /**
     * Applique les actions par defaut pour tous les tours dont l'echeance est
     * passee (l'attaquant actif rate son tour -> attaque de base auto).
     *
     * @return int nombre de tours resolus automatiquement
     */
    private function resolveOverdueTurns(GroupDungeonRun $run): int
    {
        $guard = 0;
        while (
            GroupDungeonRun::STATUS_IN_PROGRESS === $run->getStatus()
            && $run->isCombatInitialized()
            && null !== $run->getTurnDeadline()
            && $run->getTurnDeadline() <= $this->now()
            && $guard < self::MAX_AUTO_RESOLUTIONS
        ) {
            ++$guard;
            $activeId = $run->getActivePlayerId();
            $active = null !== $activeId ? $this->entityManager->getRepository(Player::class)->find($activeId) : null;
            if (null === $active || $active->isDead()) {
                // Joueur introuvable ou a terre (DON-02) : le tour passe.
                $run->advanceTurn();
                $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
                continue;
            }
            $this->applyAction($run, $active);
        }

        return $guard;
    }

    /**
     * Applique l'action reelle d'un joueur (DON-02) : le geste de son build
     * frappe la rencontre, la rencontre riposte sur lui, et le run se termine
     * — victoire quand la rencontre tombe, echec quand plus un membre ne
     * tient debout.
     */
    private function applyAction(GroupDungeonRun $run, Player $player, ?string $spellSlug = null): void
    {
        $action = $this->actionResolver->resolve($player, $spellSlug);
        $run->damageEncounter($action['damage']);

        if ($run->getEncounterHpCurrent() <= 0) {
            // DON-03 : la rencontre tombee ouvre l'etape suivante — le boss
            // seul termine le run. `currentStep` avance reellement.
            if ($run->getCurrentStep() < \count(self::STEP_RANKS) - 1) {
                $run->setCurrentStep($run->getCurrentStep() + 1);
                $this->openEncounter($run);
                $this->advanceToNextStandingMember($run);
                $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));

                return;
            }

            $run->setStatus(GroupDungeonRun::STATUS_COMPLETED);
            $run->setTurnDeadline(null);
            // Recompenses decroissantes & lockouts (ZON-20), au seul instant
            // ou le run passe complete.
            $this->rewardService->award($run);

            return;
        }

        // DON-02 : la riposte. La rencontre frappe le membre qui vient d'agir
        // — agir a un cout, et un donjon peut desormais etre perdu. DON-03 :
        // le coup est celui du monstre de l'etape — une elite frappe plus
        // fort qu'un commun, sans reglage special (GAME_ARCHETYPES §9 octies).
        $player->setLife(max(0, $player->getLife() - $this->getEncounterStrike($run)));
        if (0 === $player->getLife() && null === $player->getDiedAt()) {
            $player->setDiedAt(new \DateTime());
        }

        if ($this->everyMemberIsDown($run)) {
            $run->setStatus(GroupDungeonRun::STATUS_FAILED);
            $run->setTurnDeadline(null);

            return;
        }

        $this->advanceToNextStandingMember($run);
        $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
    }

    private function everyMemberIsDown(GroupDungeonRun $run): bool
    {
        foreach ($run->getMemberPlayers() as $member) {
            if (!$member->isDead()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Avance le tour en sautant les membres a terre — borne par la taille du
     * groupe, l'echec total etant deja ecarte par l'appelant.
     */
    private function advanceToNextStandingMember(GroupDungeonRun $run): void
    {
        $members = [];
        foreach ($run->getMemberPlayers() as $member) {
            $members[$member->getId()] = $member;
        }

        $guard = \count($members) + 1;
        do {
            $run->advanceTurn();
            $active = $members[$run->getActivePlayerId()] ?? null;
        } while (--$guard > 0 && null !== $active && $active->isDead());
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(GroupDungeonRun $run): array
    {
        $deadline = $run->getTurnDeadline();
        $monster = $run->getEncounterMonster();

        return [
            'status' => $run->getStatus(),
            'encounterHpCurrent' => $run->getEncounterHpCurrent(),
            'encounterHpMax' => $run->getEncounterHpMax(),
            'encounterHpPercent' => $run->getEncounterHpPercent(),
            // DON-03 : l'etape et la creature qui l'incarne — l'ecran dit qui
            // l'on affronte, plus une barre anonyme.
            'currentStep' => $run->getCurrentStep() + 1,
            'totalSteps' => \count(self::STEP_RANKS),
            'encounterName' => $monster?->getName(),
            'encounterRank' => $monster?->getRank()->value,
            'activePlayerId' => $run->getActivePlayerId(),
            'turnRemainingSeconds' => null !== $deadline ? max(0, $deadline->getTimestamp() - $this->now()->getTimestamp()) : null,
        ];
    }

    /**
     * Le coup de la rencontre — ARC-17b.
     *
     * **Ce chemin lisait la precision du monstre comme des degats.** DON-02
     * disait pourtant ce qu'il voulait : *le coup est celui du monstre de
     * l'etape, une elite frappe plus fort qu'un commun, sans reglage special.*
     * Il ne pouvait pas l'obtenir — aucun nombre de degats n'existait sur un
     * monstre avant ARC-17a —, et `Monster::hit` etait le seul entier
     * disponible. La meme valeur servait donc de **probabilite de toucher** en
     * combat de zone et de **degats** ici, et elle ne progresse que de 75 a 95
     * sur toute la grille : un facteur 1,27 la ou le canon en demande 2,9 entre
     * deux rangs voisins.
     *
     * Le curseur historique ne sert plus que **faute de monstre** : un palier
     * sans faune n'empeche jamais un donjon de s'ouvrir (DON-03).
     */
    private function getEncounterStrike(GroupDungeonRun $run): int
    {
        $monster = $run->getEncounterMonster();
        if (null !== $monster) {
            return MonsterDamageLaw::strikeFor($monster);
        }

        return $this->getEncounterHit();
    }

    public function getTurnSeconds(): int
    {
        $value = $this->readParameter(self::PARAM_TURN_SECONDS, self::DEFAULT_TURN_SECONDS);

        return $value > 0 ? $value : self::DEFAULT_TURN_SECONDS;
    }

    /**
     * Les PV de rencontre par membre — le curseur, jamais une copie.
     *
     * **Publique depuis ARC-17c-d** : le simulateur d'equilibrage joue la
     * rencontre de groupe, et il doit la jouer avec le curseur que le jeu
     * applique. Le recopier ferait diverger la mesure du moteur des la premiere
     * fois qu'on deplace l'un des deux — et c'est precisement ce curseur que le
     * simulateur a pour tache de fixer (GAME_ARCHETYPES § 9 octies).
     */
    public function getHpPerMember(): int
    {
        $value = $this->readParameter(self::PARAM_HP_PER_MEMBER, self::DEFAULT_HP_PER_MEMBER);

        return $value > 0 ? $value : self::DEFAULT_HP_PER_MEMBER;
    }

    private function getEncounterHit(): int
    {
        $value = $this->readParameter(self::PARAM_ENCOUNTER_HIT, self::DEFAULT_ENCOUNTER_HIT);

        return $value > 0 ? $value : self::DEFAULT_ENCOUNTER_HIT;
    }

    private function readParameter(string $name, int $default): int
    {
        $parameter = $this->entityManager->getRepository(Parameter::class)->findOneBy(['name' => $name]);

        return null !== $parameter ? (int) $parameter->getValue() : $default;
    }

    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
