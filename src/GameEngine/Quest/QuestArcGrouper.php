<?php

namespace App\GameEngine\Quest;

use App\Entity\Game\Quest;
use App\Repository\QuestRepository;

/**
 * Regroupe les quetes d'un joueur (actives ou terminees) par arc narratif
 * (`Quest::storyArc`) pour l'ecran journal (NAR-02).
 *
 * Chaque arc porte sa progression `completed/total` : `total` est le nombre de
 * quetes definies dans l'arc (source de verite : {@see QuestRepository::findByStoryArc()}),
 * `completed` le nombre de ces quetes deja terminees par le joueur. Les quetes
 * sans arc (`storyArc = null`) sont renvoyees a part, pour un groupe « Divers ».
 */
class QuestArcGrouper
{
    /** @var array<string, int[]> cache slug d'arc -> IDs des quetes de l'arc */
    private array $arcQuestIds = [];

    public function __construct(
        private readonly QuestRepository $questRepository,
    ) {
    }

    /**
     * Groupe une liste d'items portant chacun une {@see Quest} par arc narratif.
     *
     * Les arcs sont ordonnes alphabetiquement par slug ; a l'interieur d'un arc,
     * les items sont tries par `arcOrder` croissant (positions nulles en fin,
     * memes regles que {@see Quest::sortByArcOrder()}).
     *
     * @template TItem
     *
     * @param TItem[]                $items
     * @param callable(TItem): Quest $questOf           extrait la quete d'un item
     * @param int[]                  $completedQuestIds IDs des quetes terminees par le joueur (progression d'arc)
     *
     * @return array{
     *     arcs: list<array{slug: string, completed: int, total: int, items: list<TItem>}>,
     *     isolated: list<TItem>
     * }
     */
    public function group(array $items, callable $questOf, array $completedQuestIds = []): array
    {
        /** @var array<string, list<TItem>> $byArc */
        $byArc = [];
        $isolated = [];

        foreach ($items as $item) {
            $arc = $questOf($item)->getStoryArc();
            if ($arc === null) {
                $isolated[] = $item;

                continue;
            }
            $byArc[$arc][] = $item;
        }

        ksort($byArc);

        $arcs = [];
        foreach ($byArc as $slug => $arcItems) {
            $arcs[] = [
                'slug' => $slug,
                'completed' => $this->countCompletedInArc($slug, $completedQuestIds),
                'total' => \count($this->questIdsForArc($slug)),
                'items' => $this->sortItemsByArcOrder($arcItems, $questOf),
            ];
        }

        return ['arcs' => $arcs, 'isolated' => array_values($isolated)];
    }

    /**
     * @param int[] $completedQuestIds
     */
    private function countCompletedInArc(string $slug, array $completedQuestIds): int
    {
        if ($completedQuestIds === []) {
            return 0;
        }

        return \count(array_intersect($this->questIdsForArc($slug), $completedQuestIds));
    }

    /**
     * @return int[]
     */
    private function questIdsForArc(string $slug): array
    {
        if (!isset($this->arcQuestIds[$slug])) {
            $this->arcQuestIds[$slug] = array_map(
                static fn (Quest $quest): int => $quest->getId(),
                $this->questRepository->findByStoryArc($slug),
            );
        }

        return $this->arcQuestIds[$slug];
    }

    /**
     * @template TItem
     *
     * @param list<TItem>            $items
     * @param callable(TItem): Quest $questOf
     *
     * @return list<TItem>
     */
    private function sortItemsByArcOrder(array $items, callable $questOf): array
    {
        $items = array_values($items);

        usort($items, static function ($a, $b) use ($questOf): int {
            $orderA = $questOf($a)->getArcOrder();
            $orderB = $questOf($b)->getArcOrder();

            if ($orderA === $orderB) {
                return 0;
            }
            if ($orderA === null) {
                return 1;
            }
            if ($orderB === null) {
                return -1;
            }

            return $orderA <=> $orderB;
        });

        return $items;
    }
}
