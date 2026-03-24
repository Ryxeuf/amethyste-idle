<?php

namespace App\GameEngine\Progression;

use App\Entity\App\BuildPreset;
use App\Entity\App\Player;
use App\Entity\Game\Skill;
use App\Helper\PlayerSkillHelper;
use Doctrine\ORM\EntityManagerInterface;

class BuildPresetManager
{
    public const int MAX_PRESETS_PER_PLAYER = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillRespecManager $respecManager,
        private readonly SkillAcquiring $skillAcquiring,
    ) {
    }

    /**
     * @return BuildPreset[]
     */
    public function getPresets(Player $player): array
    {
        return $this->entityManager->getRepository(BuildPreset::class)->findBy(
            ['player' => $player],
            ['createdAt' => 'DESC'],
        );
    }

    public function canSave(Player $player): bool
    {
        if ($player->getSkills()->isEmpty()) {
            return false;
        }

        $count = $this->entityManager->getRepository(BuildPreset::class)->count(['player' => $player]);

        return $count < self::MAX_PRESETS_PER_PLAYER;
    }

    public function save(Player $player, string $name): ?BuildPreset
    {
        if (!$this->canSave($player)) {
            return null;
        }

        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 50) {
            return null;
        }

        $slugs = [];
        foreach ($player->getSkills() as $skill) {
            $slugs[] = $skill->getSlug();
        }
        sort($slugs);

        $preset = new BuildPreset();
        $preset->setPlayer($player);
        $preset->setName($name);
        $preset->setSkillSlugs($slugs);

        $this->entityManager->persist($preset);
        $this->entityManager->flush();

        return $preset;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function load(Player $player, BuildPreset $preset): array
    {
        if ($preset->getPlayer()->getId() !== $player->getId()) {
            return ['success' => false, 'message' => 'Ce preset ne vous appartient pas.'];
        }

        if ($player->getFight() !== null) {
            return ['success' => false, 'message' => 'Impossible de charger un preset en combat.'];
        }

        $skillRepo = $this->entityManager->getRepository(Skill::class);
        $skills = [];
        foreach ($preset->getSkillSlugs() as $slug) {
            $skill = $skillRepo->findOneBy(['slug' => $slug]);
            if ($skill === null) {
                return ['success' => false, 'message' => sprintf('La compétence « %s » n\'existe plus.', $slug)];
            }
            $skills[] = $skill;
        }

        // Calculer le cout total et vérifier les domaines
        $totalCost = 0;
        foreach ($skills as $skill) {
            $totalCost += $skill->getRequiredPoints();
        }

        if ($totalCost > PlayerSkillHelper::MAX_TOTAL_SKILL_POINTS) {
            return ['success' => false, 'message' => 'Ce preset dépasse la limite de points de compétence.'];
        }

        // Vérifier que le joueur a assez d'XP dans les domaines concernés
        if (!$this->hasEnoughDomainXp($player, $skills)) {
            return ['success' => false, 'message' => 'XP insuffisante dans un ou plusieurs domaines pour ce preset.'];
        }

        // Respec (coûte des gils)
        if (!$player->getSkills()->isEmpty()) {
            if (!$this->respecManager->canRespec($player)) {
                $cost = $this->respecManager->getRespecCost($player);

                return ['success' => false, 'message' => sprintf('Fonds insuffisants pour la redistribution (%d gils nécessaires).', $cost)];
            }

            if (!$this->respecManager->respec($player)) {
                return ['success' => false, 'message' => 'Échec de la redistribution.'];
            }
        }

        // Trier les skills par ordre de prérequis (ceux sans prérequis d'abord)
        $sorted = $this->sortByRequirements($skills);

        // Acquérir chaque skill
        foreach ($sorted as $skill) {
            $this->skillAcquiring->acquireSkill($skill);
        }

        return ['success' => true, 'message' => sprintf('Build « %s » chargé avec succès !', $preset->getName())];
    }

    public function delete(Player $player, BuildPreset $preset): bool
    {
        if ($preset->getPlayer()->getId() !== $player->getId()) {
            return false;
        }

        $this->entityManager->remove($preset);
        $this->entityManager->flush();

        return true;
    }

    /**
     * @param Skill[] $skills
     */
    private function hasEnoughDomainXp(Player $player, array $skills): bool
    {
        // Simuler l'allocation : calculer le coût total par domaine
        // Chaque skill coûte requiredPoints dans UN de ses domaines
        // On doit vérifier qu'il existe une allocation valide
        $domainTotals = [];
        foreach ($player->getDomainExperiences() as $de) {
            $domainTotals[$de->getDomain()->getId()] = $de->getTotalExperience();
        }

        // Heuristique simple : pour chaque skill, vérifier que AU MOINS un domaine
        // a assez de XP totale pour couvrir le coût
        // (La vérification exacte nécessiterait un solveur, on fait confiance à acquireSkill)
        foreach ($skills as $skill) {
            $canFit = false;
            foreach ($skill->getDomains() as $domain) {
                if (($domainTotals[$domain->getId()] ?? 0) >= $skill->getRequiredPoints()) {
                    $canFit = true;
                    break;
                }
            }
            if (!$canFit) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Skill[] $skills
     *
     * @return Skill[]
     */
    private function sortByRequirements(array $skills): array
    {
        $slugSet = [];
        foreach ($skills as $skill) {
            $slugSet[$skill->getSlug()] = $skill;
        }

        $sorted = [];
        $added = [];

        // Répéter jusqu'à tout placer (protection boucle infinie = max itérations)
        $maxIter = count($skills) * count($skills);
        $iter = 0;
        while (count($sorted) < count($skills) && $iter < $maxIter) {
            foreach ($skills as $skill) {
                if (isset($added[$skill->getSlug()])) {
                    continue;
                }

                $reqsMet = true;
                foreach ($skill->getRequirements() as $req) {
                    if (isset($slugSet[$req->getSlug()]) && !isset($added[$req->getSlug()])) {
                        $reqsMet = false;
                        break;
                    }
                }

                if ($reqsMet) {
                    $sorted[] = $skill;
                    $added[$skill->getSlug()] = true;
                }
            }
            ++$iter;
        }

        return $sorted;
    }
}
