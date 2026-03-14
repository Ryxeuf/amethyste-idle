<?php

namespace App\GameEngine\Quest;

use App\Entity\Game\Quest;

class QuestTrackingFormater
{
    public function formatTracking(Quest $quest): array
    {
        $tracking = [];
        $requirements = $quest->getRequirements();
        $monsters = $this->formatMonsters($requirements);
        if (!empty($monsters)) {
            $tracking['monsters'] = $monsters;
        }

        return $tracking;
    }

    public function formatMonsters(array $requirements): array
    {
        if (!isset($requirements['monsters'])) {
            return [];
        }
        $monsters = [];
        foreach ($requirements['monsters'] as $monsterRequirement) {
            $monsters[] = [
                'count' => 0,
                'necessary' => $monsterRequirement['count'],
                'slug' => $monsterRequirement['slug'],
                'name' => $monsterRequirement['name'],
            ];
        }

        return $monsters;
    }
}
