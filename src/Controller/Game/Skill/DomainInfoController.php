<?php

namespace App\Controller\Game\Skill;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\GameEngine\Player\PlayerActionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/game/skills/domain/{id}', name: 'app_game_domain_info')]
class DomainInfoController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerActionHelper $playerActionHelper,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $domain = $this->entityManager->getRepository(Domain::class)->find($id);

        if (!$domain) {
            throw $this->createNotFoundException('Domaine non trouvé');
        }

        // Extraire les filons exploitables et outils équipables depuis les skills du domaine
        $allSpots = [];
        $allToolSlugs = [];
        $unlockedSpots = $this->playerActionHelper->getHarvestSpots();
        $unlockedToolSlugs = $this->playerActionHelper->getEquippableToolSlugs();

        foreach ($domain->getSkills() as $skill) {
            foreach ($skill->getActions() ?? [] as $action) {
                if (($action['action'] ?? '') === PlayerActionHelper::HARVEST) {
                    foreach ($action['spots'] ?? [] as $spot) {
                        $allSpots[$spot] = [
                            'slug' => $spot,
                            'skill' => $skill->getTitle(),
                            'unlocked' => \in_array($spot, $unlockedSpots, true),
                        ];
                    }
                }
                if (($action['action'] ?? '') === PlayerActionHelper::EQUIP_TOOL) {
                    foreach ($action['slugs'] ?? [] as $toolSlug) {
                        $allToolSlugs[$toolSlug] = [
                            'slug' => $toolSlug,
                            'skill' => $skill->getTitle(),
                            'unlocked' => \in_array($toolSlug, $unlockedToolSlugs, true),
                        ];
                    }
                }
            }
        }

        // Charger les noms des outils depuis la BDD
        if (!empty($allToolSlugs)) {
            $tools = $this->entityManager->getRepository(Item::class)->findBy([
                'slug' => array_keys($allToolSlugs),
            ]);
            foreach ($tools as $tool) {
                if (isset($allToolSlugs[$tool->getSlug()])) {
                    $allToolSlugs[$tool->getSlug()]['name'] = $tool->getName();
                }
            }
        }

        return $this->render('game/skills/domain_info.html.twig', [
            'domain' => $domain,
            'harvestSpots' => $allSpots,
            'equippableTools' => $allToolSlugs,
        ]);
    }

}
