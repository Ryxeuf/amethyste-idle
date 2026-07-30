<?php

namespace App\Controller\Game\Skill;

use App\Dto\Domain\PlayerDomain;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\GameEngine\Player\PlayerActionHelper;
use App\GameEngine\Progression\DomainAccessManager;
use App\GameEngine\Progression\DomainCatalogView;
use App\Helper\PlayerDomainHelper;
use App\Helper\PlayerHelper;
use App\Helper\PlayerSkillHelper;
use App\Transformer\PlayerSkillTransformer;
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
        private readonly PlayerDomainHelper $playerDomainHelper,
        private readonly PlayerSkillTransformer $playerSkillTransformer,
        private readonly PlayerSkillHelper $skillHelper,
        private readonly DomainAccessManager $domainAccessManager,
        private readonly DomainCatalogView $catalogView,
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $domain = $this->entityManager->getRepository(Domain::class)->find($id);

        if (!$domain) {
            throw $this->createNotFoundException('Domaine non trouvé');
        }

        // ONB-09 — les trois etats. Cet ecran montrait **tous les nœuds de
        // n'importe quel domaine a n'importe qui** : le detail technique etait
        // gratuit, et le parchemin n'achetait rien. Un arbre ferme rend
        // desormais sa carte de catalogue — ce qu'on y apprend, ce qu'il permet
        // d'equiper, ou trouver son parchemin — et **aucun nœud**.
        //
        // Le retour anticipe est deliberement place avant toute lecture des
        // competences : un jour ou l'autre, quelqu'un ajoutera une variable au
        // gabarit, et il vaut mieux qu'elle n'ait jamais ete calculee.
        $player = $this->playerHelper->getPlayer();
        if ($player === null || !$this->domainAccessManager->isOpen($player, $domain)) {
            return $this->render('game/skills/domain_catalog_card.html.twig', [
                'card' => $this->catalogView->card($domain, $player),
            ]);
        }

        // Construire le DTO PlayerDomain avec les stats du joueur
        $domainModel = new PlayerDomain($domain);
        $domainExperience = $this->playerDomainHelper->getDomainExperience($domain);

        if ($domainExperience !== null) {
            $domainModel->availableExperience = $domainExperience->getAvailableExperience();
            $domainModel->totalExperience = $domainExperience->getTotalExperience();
            $domainModel->damage = $domainExperience->getDamage();
            $domainModel->hit = $domainExperience->getHit();
            $domainModel->critical = $domainExperience->getCritical();
        }

        // Transformer les skills en DTOs avec acquired/canBeAcquired
        foreach ($domain->getSkills() as $skill) {
            $skillDto = $this->playerSkillTransformer->transform($skill);
            $skillDto->acquired = $this->skillHelper->hasSkill($skill);
            $skillDto->canBeAcquired = $this->skillHelper->canAcquireSkill($skill);
            $domainModel->skills[] = $skillDto;
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

        // Charger les noms des spots depuis la BDD
        if (!empty($allSpots)) {
            $objectLayers = $this->entityManager->getRepository(\App\Entity\App\ObjectLayer::class)->findBy([
                'slug' => array_keys($allSpots),
            ]);
            foreach ($objectLayers as $objectLayer) {
                if (isset($allSpots[$objectLayer->getSlug()])) {
                    $allSpots[$objectLayer->getSlug()]['name'] = $objectLayer->getName();
                }
            }
        }

        return $this->render('game/skills/domain_info.html.twig', [
            'domain' => $domainModel,
            'harvestSpots' => $allSpots,
            'equippableTools' => $allToolSlugs,
        ]);
    }
}
