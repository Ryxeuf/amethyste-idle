<?php

namespace App\Helper;

use App\Dto\Fight\TimelineItem;
use App\Dto\Fight\TimelineMobItem;
use App\Dto\Fight\TimelinePlayerItem;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;

class FightTimelineHelper
{
    final public const TIMELINE_LENGTH = 10;

    private ?array $timeline = null;

    protected function getTimeLine(Fight $fight)
    {
        $count = $this->getTimelineCount($fight);
        $players = $this->getTimelinePlayers($fight);

        $order = [];
        foreach ($players as $player) {
            for ($i = 1; $i <= $player->getSpeed(); ++$i) {
                $speed = $player->getSpeed();
                if ($player instanceof Mob) {
                    $speed *= 0.999;
                }
                $index = round($i * $count / $speed, 3);
                $order[] = [
                    'object' => $player,
                    'speed' => $speed,
                    'index' => $index,
                ];
            }
        }

        usort($order, function ($a, $b) {
            if ($a['index'] < $b['index']) {
                return -1;
            } elseif ($a['index'] == $b['index']) {
                if ($a['speed'] == $b['speed']) {
                    if ($a['object']::class == $b['object']::class) {
                        return 0;
                    }

                    return $a['object']::class == Player::class ? -1 : 1;
                }

                return $a['speed'] > $b['speed'] ? -1 : 1;
            }

            return 1;
        }
        );

        return $order;
    }

    protected function getTimelineCount(Fight $fight): int
    {
        $speed = $fight->getMob()->getSpeed();
        foreach ($fight->getPlayers() as $player) {
            $speed += $player->getSpeed();
        }

        return $speed;
    }

    /**
     * @return array|Mob[]|Player[]
     */
    protected function getTimelinePlayers(Fight $fight): array
    {
        $characters = [$fight->getMob()];
        foreach ($fight->getPlayers() as $player) {
            $characters[] = $player;
        }

        return $characters;
    }

    protected function generateTimelinePlayerItem(Player $player): TimelinePlayerItem
    {
        return new TimelinePlayerItem($player->getName(), 'demo');
    }

    protected function generateTimelineMobItem(Mob $mob): TimelineMobItem
    {
        return new TimelineMobItem($mob->getMonster()->getName(), $mob->getMonster()->getSlug());
    }

    protected function generateTimeline(Fight $fight, bool $force = false): array
    {
        if (!is_array($this->timeline) || $force) {
            $sequence = $this->getTimeLine($fight);
            $count = is_countable($sequence) ? count($sequence) : 0;
            $this->timeline = [];
            for ($i = 0; $i < $count * 2; ++$i) {
                $object = $sequence[$i % $count]['object'];
                if ($object instanceof Player) {
                    $this->timeline[$i] = $this->generateTimelinePlayerItem($object);
                } elseif ($object instanceof Mob) {
                    $this->timeline[$i] = $this->generateTimelineMobItem($object);
                }
            }
        }

        return $this->timeline;
    }

    public function getCurrentTimeline(Fight $fight, bool $force = false): array
    {
        $timeline = $this->generateTimeline($fight, $force);
        $step = $fight->getStep() % $this->getTimelineCount($fight);

        return array_slice($timeline, $step, self::TIMELINE_LENGTH);
    }

    public function getNextItem(Fight $fight, bool $force = false): ?TimelineItem
    {
        $timeline = $this->generateTimeline($fight, $force);
        $step = $fight->getStep() % $this->getTimelineCount($fight);

        return $timeline[$step] ?? null;
    }
}
