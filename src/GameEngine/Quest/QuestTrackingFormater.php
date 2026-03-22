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

        $collect = $this->formatCollect($requirements);
        if (!empty($collect)) {
            $tracking['collect'] = $collect;
        }

        $craft = $this->formatCraft($requirements);
        if (!empty($craft)) {
            $tracking['craft'] = $craft;
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

    public function formatCollect(array $requirements): array
    {
        if (!isset($requirements['collect'])) {
            return [];
        }
        $collect = [];
        foreach ($requirements['collect'] as $slug => $count) {
            $collect[] = [
                'count' => 0,
                'necessary' => $count,
                'slug' => $slug,
            ];
        }

        return $collect;
    }

    public function formatCraft(array $requirements): array
    {
        if (!isset($requirements['craft'])) {
            return [];
        }
        $craft = [];
        foreach ($requirements['craft'] as $slug => $count) {
            $craft[] = [
                'count' => 0,
                'necessary' => $count,
                'slug' => $slug,
            ];
        }

        return $craft;
    }
}
