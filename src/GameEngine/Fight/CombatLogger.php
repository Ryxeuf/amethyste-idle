<?php

namespace App\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\FightLog;
use App\Entity\App\Player;
use App\Entity\CharacterInterface;
use Doctrine\ORM\EntityManagerInterface;

class CombatLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function logFightStart(Fight $fight): void
    {
        $playerNames = [];
        foreach ($fight->getPlayers() as $p) {
            $playerNames[] = $p->getName();
        }
        $mobNames = [];
        foreach ($fight->getMobs() as $m) {
            $mobNames[] = $m->getName();
        }

        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_FIGHT_START,
            sprintf('Combat engage : %s vs %s', implode(', ', $playerNames), implode(', ', $mobNames)),
            ['players' => $playerNames, 'mobs' => $mobNames]
        );
    }

    public function logAttack(Fight $fight, CharacterInterface $attacker, CharacterInterface $target, int $damage): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($attacker),
            $attacker->getId(),
            $this->getCharacterName($attacker),
            FightLog::TYPE_ATTACK,
            sprintf('%s attaque %s pour %d degats !', $this->getCharacterName($attacker), $this->getCharacterName($target), $damage),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'target_name' => $this->getCharacterName($target),
                'damage' => $damage,
            ]
        );
    }

    public function logSpell(Fight $fight, CharacterInterface $caster, CharacterInterface $target, string $spellName, bool $hit): void
    {
        if ($hit) {
            $this->log(
                $fight,
                $fight->getStep(),
                $this->getActorType($caster),
                $caster->getId(),
                $this->getCharacterName($caster),
                FightLog::TYPE_SPELL,
                sprintf('%s lance %s sur %s !', $this->getCharacterName($caster), $spellName, $this->getCharacterName($target)),
                [
                    'spell' => $spellName,
                    'target_id' => $target->getId(),
                    'target_type' => $this->getActorType($target),
                    'target_name' => $this->getCharacterName($target),
                    'hit' => true,
                ]
            );
        } else {
            $this->log(
                $fight,
                $fight->getStep(),
                $this->getActorType($caster),
                $caster->getId(),
                $this->getCharacterName($caster),
                FightLog::TYPE_MISS,
                sprintf('%s rate %s !', $this->getCharacterName($caster), $spellName),
                [
                    'spell' => $spellName,
                    'target_id' => $target->getId(),
                    'target_type' => $this->getActorType($target),
                    'target_name' => $this->getCharacterName($target),
                    'hit' => false,
                ]
            );
        }
    }

    public function logDamage(Fight $fight, CharacterInterface $target, int $damage, string $source): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_DAMAGE,
            sprintf('%s subit %d degats (%s).', $this->getCharacterName($target), $damage, $source),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'target_name' => $this->getCharacterName($target),
                'damage' => $damage,
                'source' => $source,
                'remaining_hp' => $target->getLife(),
            ]
        );
    }

    public function logHeal(Fight $fight, CharacterInterface $target, int $heal, string $source): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_HEAL,
            sprintf('%s recupere %d PV (%s).', $this->getCharacterName($target), $heal, $source),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'target_name' => $this->getCharacterName($target),
                'heal' => $heal,
                'source' => $source,
                'remaining_hp' => $target->getLife(),
            ]
        );
    }

    public function logDeath(Fight $fight, CharacterInterface $character): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($character),
            $character->getId(),
            $this->getCharacterName($character),
            FightLog::TYPE_DEATH,
            sprintf('%s est vaincu !', $this->getCharacterName($character)),
            [
                'character_type' => $this->getActorType($character),
            ]
        );
    }

    public function logFlee(Fight $fight, CharacterInterface $character, bool $success): void
    {
        $type = $success ? FightLog::TYPE_FLEE : FightLog::TYPE_FLEE_FAIL;
        $message = $success
            ? sprintf('%s prend la fuite !', $this->getCharacterName($character))
            : sprintf('%s tente de fuir mais echoue !', $this->getCharacterName($character));

        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($character),
            $character->getId(),
            $this->getCharacterName($character),
            $type,
            $message,
            ['success' => $success]
        );
    }

    public function logStatusApply(Fight $fight, CharacterInterface $target, string $effectName): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_STATUS_APPLY,
            sprintf('%s est affecte par %s !', $this->getCharacterName($target), $effectName),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'target_name' => $this->getCharacterName($target),
                'effect' => $effectName,
            ]
        );
    }

    public function logStatusTick(Fight $fight, CharacterInterface $target, string $effectName, int $value, string $tickType): void
    {
        $message = $tickType === 'damage'
            ? sprintf('%s subit %d degats de %s.', $this->getCharacterName($target), $value, $effectName)
            : sprintf('%s recupere %d PV grace a %s.', $this->getCharacterName($target), $value, $effectName);

        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_STATUS_TICK,
            $message,
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'target_name' => $this->getCharacterName($target),
                'effect' => $effectName,
                'value' => $value,
                'tick_type' => $tickType,
            ]
        );
    }

    public function logCritical(Fight $fight, CharacterInterface $attacker): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($attacker),
            $attacker->getId(),
            $this->getCharacterName($attacker),
            FightLog::TYPE_CRITICAL,
            sprintf('Coup critique de %s !', $this->getCharacterName($attacker)),
            []
        );
    }

    public function logSynergy(Fight $fight, string $synergyLabel): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_SYNERGY,
            sprintf('Synergie %s activee !', $synergyLabel),
            ['synergy' => $synergyLabel]
        );
    }

    public function logResist(Fight $fight, CharacterInterface $target, string $element): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_RESIST,
            sprintf('%s resiste a %s !', $this->getCharacterName($target), $element),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'element' => $element,
            ]
        );
    }

    public function logShield(Fight $fight, CharacterInterface $target, int $absorbed): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_SHIELD,
            sprintf('Le bouclier de %s absorbe %d degats !', $this->getCharacterName($target), $absorbed),
            [
                'target_id' => $target->getId(),
                'target_type' => $this->getActorType($target),
                'absorbed' => $absorbed,
            ]
        );
    }

    public function logImmobilized(Fight $fight, CharacterInterface $character): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($character),
            $character->getId(),
            $this->getCharacterName($character),
            FightLog::TYPE_IMMOBILIZED,
            sprintf('%s est immobilise !', $this->getCharacterName($character)),
            []
        );
    }

    public function logItem(Fight $fight, CharacterInterface $user, string $itemName): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($user),
            $user->getId(),
            $this->getCharacterName($user),
            FightLog::TYPE_ITEM,
            sprintf('%s utilise %s !', $this->getCharacterName($user), $itemName),
            ['item' => $itemName]
        );
    }

    public function logSummon(Fight $fight, CharacterInterface $summoner, string $summonedName, int $count): void
    {
        $message = $count > 1
            ? sprintf('%s invoque %d %s !', $this->getCharacterName($summoner), $count, $summonedName)
            : sprintf('%s invoque un %s !', $this->getCharacterName($summoner), $summonedName);

        $this->log(
            $fight,
            $fight->getStep(),
            $this->getActorType($summoner),
            $summoner->getId(),
            $this->getCharacterName($summoner),
            FightLog::TYPE_SUMMON,
            $message,
            [
                'summoned_name' => $summonedName,
                'count' => $count,
            ]
        );
    }

    public function logBossPhaseChange(Fight $fight, CharacterInterface $boss, string $phaseName): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_BOSS_PHASE,
            sprintf('%s entre en %s !', $this->getCharacterName($boss), $phaseName),
            [
                'boss_id' => $boss->getId(),
                'boss_type' => $this->getActorType($boss),
                'boss_name' => $this->getCharacterName($boss),
                'phase_name' => $phaseName,
            ]
        );
    }

    public function logPlayerJoined(Fight $fight, Player $player): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_PLAYER_JOINED,
            sprintf('%s rejoint le combat !', $player->getName()),
            ['player_id' => $player->getId(), 'player_name' => $player->getName()]
        );
    }

    public function logVictory(Fight $fight): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_VICTORY,
            'Victoire !',
            []
        );
    }

    public function logDefeat(Fight $fight): void
    {
        $this->log(
            $fight,
            $fight->getStep(),
            FightLog::ACTOR_SYSTEM,
            null,
            'Systeme',
            FightLog::TYPE_DEFEAT,
            'Defaite...',
            []
        );
    }

    /**
     * @return FightLog[]
     */
    public function getLogsForFight(Fight $fight): array
    {
        return $this->entityManager->getRepository(FightLog::class)->findBy(
            ['fight' => $fight],
            ['turn' => 'ASC', 'id' => 'ASC']
        );
    }

    private function log(
        Fight $fight,
        int $turn,
        string $actorType,
        ?int $actorId,
        string $actorName,
        string $type,
        string $message,
        ?array $metadata,
    ): void {
        $log = new FightLog();
        $log->setFight($fight);
        $log->setTurn($turn);
        $log->setActorType($actorType);
        $log->setActorId($actorId);
        $log->setActorName($actorName);
        $log->setType($type);
        $log->setMessage($message);
        $log->setMetadata($metadata);

        $this->entityManager->persist($log);
    }

    private function getActorType(CharacterInterface $character): string
    {
        return $character instanceof Player ? FightLog::ACTOR_PLAYER : FightLog::ACTOR_MOB;
    }

    private function getCharacterName(CharacterInterface $character): string
    {
        return $character->getName();
    }
}
