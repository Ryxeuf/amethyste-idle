<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
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
    public const DEFAULT_HP_PER_MEMBER = 200;
    public const PARAM_HP_PER_MEMBER = 'zone.dungeon.encounter_hp_per_member';

    private const MAX_AUTO_RESOLUTIONS = 100;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupDungeonCombatPublisher $publisher,
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
    public function act(Player $player, GroupDungeonRun $run): array
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

        $this->applyAction($run, $player);
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
        $hp = $this->getHpPerMember() * \count($order);
        $run->setEncounterHp($hp, $hp);
        $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
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
            if (null === $active) {
                // Joueur introuvable : on avance simplement le tour.
                $run->advanceTurn();
                $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
                continue;
            }
            $this->applyAction($run, $active);
        }

        return $guard;
    }

    /**
     * Applique l'attaque de base d'un joueur a la rencontre, avance le tour, et
     * complete le run si la rencontre tombe.
     */
    private function applyAction(GroupDungeonRun $run, Player $player): void
    {
        $damage = max(1, $player->getHit());
        $run->damageEncounter($damage);

        if ($run->getEncounterHpCurrent() <= 0) {
            $run->setStatus(GroupDungeonRun::STATUS_COMPLETED);
            $run->setTurnDeadline(null);

            return;
        }

        $run->advanceTurn();
        $run->setTurnDeadline($this->now()->modify(sprintf('+%d seconds', $this->getTurnSeconds())));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(GroupDungeonRun $run): array
    {
        $deadline = $run->getTurnDeadline();

        return [
            'status' => $run->getStatus(),
            'encounterHpCurrent' => $run->getEncounterHpCurrent(),
            'encounterHpMax' => $run->getEncounterHpMax(),
            'encounterHpPercent' => $run->getEncounterHpPercent(),
            'activePlayerId' => $run->getActivePlayerId(),
            'turnRemainingSeconds' => null !== $deadline ? max(0, $deadline->getTimestamp() - $this->now()->getTimestamp()) : null,
        ];
    }

    public function getTurnSeconds(): int
    {
        $value = $this->readParameter(self::PARAM_TURN_SECONDS, self::DEFAULT_TURN_SECONDS);

        return $value > 0 ? $value : self::DEFAULT_TURN_SECONDS;
    }

    private function getHpPerMember(): int
    {
        $value = $this->readParameter(self::PARAM_HP_PER_MEMBER, self::DEFAULT_HP_PER_MEMBER);

        return $value > 0 ? $value : self::DEFAULT_HP_PER_MEMBER;
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
