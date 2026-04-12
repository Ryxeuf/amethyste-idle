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

        $talkTo = $this->formatTalkTo($requirements);
        if (!empty($talkTo)) {
            $tracking['talk_to'] = $talkTo;
        }

        $bossChallenge = $this->formatBossChallenge($requirements);
        if (!empty($bossChallenge)) {
            $tracking['boss_challenge'] = $bossChallenge;
        }

        $defend = $this->formatDefend($requirements);
        if (!empty($defend)) {
            $tracking['defend'] = $defend;
        }

        $escort = $this->formatEscort($requirements);
        if (!empty($escort)) {
            $tracking['escort'] = $escort;
        }

        $puzzle = $this->formatPuzzle($requirements);
        if (!empty($puzzle)) {
            $tracking['puzzle'] = $puzzle;
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

    /**
     * Format talk_to requirements into tracking entries (enquête quests).
     *
     * Requirements format: [['pnj_id' => 5, 'name' => 'Marie la Herboriste']]
     *
     * @return array<int, array{count: int, necessary: int, pnj_id: int, name: string}>
     */
    public function formatTalkTo(array $requirements): array
    {
        if (!isset($requirements['talk_to'])) {
            return [];
        }
        $talkTo = [];
        foreach ($requirements['talk_to'] as $entry) {
            $talkTo[] = [
                'count' => 0,
                'necessary' => 1,
                'pnj_id' => $entry['pnj_id'],
                'name' => $entry['name'] ?? 'PNJ inconnu',
            ];
        }

        return $talkTo;
    }

    /**
     * Format boss_challenge requirements into tracking entries.
     *
     * Requirements format: [['monster_slug' => 'forest_guardian', 'name' => 'Gardien', 'conditions' => ['no_heal' => true]]]
     *
     * @return array<int, array{count: int, necessary: int, monster_slug: string, name: string, conditions: array<string, mixed>}>
     */
    public function formatBossChallenge(array $requirements): array
    {
        if (!isset($requirements['boss_challenge'])) {
            return [];
        }
        $bossChallenge = [];
        foreach ($requirements['boss_challenge'] as $entry) {
            $bossChallenge[] = [
                'count' => 0,
                'necessary' => 1,
                'monster_slug' => $entry['monster_slug'],
                'name' => $entry['name'] ?? $entry['monster_slug'],
                'conditions' => $entry['conditions'] ?? [],
            ];
        }

        return $bossChallenge;
    }

    /**
     * Format defend requirements (kill monsters in a specific zone).
     *
     * Requirements format: [['monster_slug' => 'zombie', 'count' => 5, 'map_id' => 3, 'name' => 'Zombie', 'zone_name' => 'Forêt']]
     *
     * @return array<int, array{count: int, necessary: int, monster_slug: string, map_id: int, name: string, zone_name: string}>
     */
    public function formatDefend(array $requirements): array
    {
        if (!isset($requirements['defend'])) {
            return [];
        }
        $defend = [];
        foreach ($requirements['defend'] as $entry) {
            $defend[] = [
                'count' => 0,
                'necessary' => $entry['count'] ?? 1,
                'monster_slug' => $entry['monster_slug'],
                'map_id' => $entry['map_id'],
                'name' => $entry['name'] ?? $entry['monster_slug'],
                'zone_name' => $entry['zone_name'] ?? 'Zone inconnue',
            ];
        }

        return $defend;
    }

    /**
     * Format escort requirements (reach a destination on a specific map).
     *
     * Requirements format: [['destination_map_id' => 4, 'destination_coordinates' => '15.10', 'name' => 'Escorter le marchand']]
     *
     * @return array<int, array{count: int, necessary: int, destination_map_id: int, destination_coordinates: string, name: string}>
     */
    public function formatEscort(array $requirements): array
    {
        if (!isset($requirements['escort'])) {
            return [];
        }
        $escort = [];
        foreach ($requirements['escort'] as $entry) {
            $escort[] = [
                'count' => 0,
                'necessary' => 1,
                'destination_map_id' => $entry['destination_map_id'],
                'destination_coordinates' => $entry['destination_coordinates'],
                'name' => $entry['name'] ?? 'Escorte',
            ];
        }

        return $escort;
    }

    /**
     * Format puzzle requirements (solve a riddle by answering correctly).
     *
     * Requirements format: [['pnj_id' => 5, 'answer_key' => 'crystal', 'name' => 'Énigme du Sphinx']]
     *
     * @return array<int, array{count: int, necessary: int, pnj_id: int, answer_key: string, name: string}>
     */
    public function formatPuzzle(array $requirements): array
    {
        if (!isset($requirements['puzzle'])) {
            return [];
        }
        $puzzle = [];
        foreach ($requirements['puzzle'] as $entry) {
            $puzzle[] = [
                'count' => 0,
                'necessary' => 1,
                'pnj_id' => $entry['pnj_id'],
                'answer_key' => $entry['answer_key'],
                'name' => $entry['name'] ?? 'Énigme',
            ];
        }

        return $puzzle;
    }
}
