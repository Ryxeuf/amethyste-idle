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

        $deliver = $this->formatDeliver($requirements);
        if (!empty($deliver)) {
            $tracking['deliver'] = $deliver;
        }

        $explore = $this->formatExplore($requirements);
        if (!empty($explore)) {
            $tracking['explore'] = $explore;
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

    /**
     * Format deliver requirements into tracking entries.
     *
     * Requirements format: [['item_slug' => 'mushroom', 'pnj_id' => 2, 'quantity' => 3, 'name' => 'Champignon']]
     *
     * @return array<int, array{count: int, necessary: int, item_slug: string, pnj_id: int, name: string}>
     */
    public function formatDeliver(array $requirements): array
    {
        if (!isset($requirements['deliver'])) {
            return [];
        }
        $deliver = [];
        foreach ($requirements['deliver'] as $entry) {
            $deliver[] = [
                'count' => 0,
                'necessary' => $entry['quantity'] ?? 1,
                'item_slug' => $entry['item_slug'],
                'pnj_id' => $entry['pnj_id'],
                'name' => $entry['name'] ?? $entry['item_slug'],
            ];
        }

        return $deliver;
    }

    /**
     * Format explore requirements into tracking entries.
     *
     * Requirements format: [['map_id' => 1, 'coordinates' => '15.20', 'name' => 'La Clairiere']]
     *
     * @return array<int, array{count: int, necessary: int, map_id: int, coordinates: string|null, name: string}>
     */
    public function formatExplore(array $requirements): array
    {
        if (!isset($requirements['explore'])) {
            return [];
        }
        $explore = [];
        foreach ($requirements['explore'] as $entry) {
            $explore[] = [
                'count' => 0,
                'necessary' => 1,
                'map_id' => $entry['map_id'],
                'coordinates' => $entry['coordinates'] ?? null,
                'name' => $entry['name'] ?? 'Zone inconnue',
            ];
        }

        return $explore;
    }
}
