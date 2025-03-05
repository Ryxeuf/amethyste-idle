<?php

namespace App\GameEngine\Player;

use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerQuestCompleted;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;

class PnjDialogParser
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly PlayerHelper $playerHelper)
    {
    }

    public function parseDialog(array $dialog)
    {
//        $dialog = [
//            0 => [
//                "next" => 1,
//                "text" => "Bien le bonjour",
//            ],
//            1 => [
//                "conditional_next" => [
//                    [
//                        "next" => 4,
//                        "next_condition" => [
//                            "quest_not" => [1],
//                        ],
//                    ],
//                    [
//                        "next" => 2,
//                        "next_condition" => [
//                            "quest" => [1],
//                        ],
//                    ],
//                    [
//                        'next' => 3,
//                    ],
//                ],
//                "text" => "Bienvenue dans le monde d'améthyste, je m'appelle Hello",
//            ],
//            2 => [
//                "text" => "Merci d'avoir tué ces zombies pour moi",
//            ],
//            3 => [
//                "text" => "As-tu tue tous les zombies pour moi ?",
//            ],
//            4 => [
//                "text" => "J'ai un problème de zombies envahissants, pourrais tu m'en débarrasser ?",
//                "choices" => [
//                    0 => [
//                        "text" => "Oui",
//                        "datas" => [
//                            "quest" => 1,
//                        ],
//                        "action" => "quest_offer",
//                    ],
//                    1 => [
//                        "text" => "Non",
//                        "action" => "close",
//                    ],
//                ],
//            ],
//        ];
//        return $dialog;

        foreach ($dialog as $idx => $sentence) {
            if (isset($sentence['conditional_next'])) {
                foreach ($sentence['conditional_next'] as $nextCondition) {
                    $hasNext = true;
                    if (isset($nextCondition['next_condition'])) {
                        foreach ($nextCondition['next_condition'] as $condition => $value) {
                            switch ($condition) {
                                case 'quest_not':
                                    $hasNext &= $this->questNot($value);
                                    break;
                                case 'quest':
                                    $hasNext &= $this->quest($value);
                                    break;
                            }
                        }
                    }
                    if ($hasNext) {
                        $dialog[$idx]['next'] = $nextCondition['next'];
                        break;
                    }

                }
                unset($dialog[$idx]['conditional_next']);
            }
        }

        return $dialog;
    }

    private function questNot(array $ids): bool
    {
        $currentQuests = $this->entityManager->getRepository(PlayerQuest::class)->findBy(['player' => $this->playerHelper->getPlayer(), 'quest' => $ids]);
        $completedQuests = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $this->playerHelper->getPlayer(), 'quest' => $ids]);

        return (count($currentQuests) + count($completedQuests)) === 0;
    }

    private function quest(array $ids): bool
    {
        $completedQuests = $this->entityManager->getRepository(PlayerQuestCompleted::class)->findBy(['player' => $this->playerHelper->getPlayer(), 'quest' => $ids]);

        return count($completedQuests) >= count($ids);
    }
}