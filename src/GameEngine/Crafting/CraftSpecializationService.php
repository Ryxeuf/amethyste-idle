<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\Player;
use App\Enum\CraftSpecialization;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gere le choix et l'application des specialisations de metier (task 122).
 *
 * Un joueur peut choisir une specialisation irreversible une fois qu'il a atteint
 * le seuil d'XP total dans le domaine correspondant. La specialisation accorde un
 * bonus de chance d'amelioration de qualite lors du craft sur le metier associe.
 */
class CraftSpecializationService
{
    /**
     * Seuil d'XP de domaine requis pour debloquer le choix d'une specialisation.
     */
    public const REQUIRED_DOMAIN_XP = 500;

    /**
     * Bonus additionnel de chance d'amelioration de qualite (en %) sur le craft
     * correspondant a la specialisation (ex: +20 ajoute au `skillLevel * 2` base).
     */
    public const QUALITY_BONUS_CHANCE = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Retourne la liste des specialisations disponibles.
     *
     * @return CraftSpecialization[]
     */
    public function getAvailableSpecializations(): array
    {
        return CraftSpecialization::cases();
    }

    /**
     * Verifie si le joueur peut actuellement choisir une specialisation.
     *
     * @return array{ok: bool, reason: string}
     */
    public function canChoose(Player $player): array
    {
        if ($player->hasCraftSpecialization()) {
            return [
                'ok' => false,
                'reason' => sprintf(
                    'Vous etes deja specialise : %s. Le choix est irreversible.',
                    $player->getCraftSpecialization()->label()
                ),
            ];
        }

        if ($this->getMaxDomainXp($player) < self::REQUIRED_DOMAIN_XP) {
            return [
                'ok' => false,
                'reason' => sprintf(
                    'Atteignez %d XP dans un domaine d\'artisanat pour debloquer une specialisation.',
                    self::REQUIRED_DOMAIN_XP
                ),
            ];
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Attribue une specialisation au joueur (irreversible).
     *
     * @return array{success: bool, message: string}
     */
    public function choose(Player $player, CraftSpecialization $specialization): array
    {
        $check = $this->canChoose($player);
        if (!$check['ok']) {
            return ['success' => false, 'message' => $check['reason']];
        }

        $player->setCraftSpecialization($specialization);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf(
                'Felicitations ! Vous etes desormais %s.',
                $specialization->label()
            ),
        ];
    }

    /**
     * Retourne le bonus de chance d'amelioration de qualite (en %) pour un craft donne.
     */
    public function getQualityBonusFor(Player $player, string $craft): int
    {
        return $player->isSpecializedIn($craft) ? self::QUALITY_BONUS_CHANCE : 0;
    }

    /**
     * Retourne la plus grande XP de domaine parmi les domaines de craft.
     */
    private function getMaxDomainXp(Player $player): int
    {
        $craftSlugs = array_map(
            static fn (CraftSpecialization $c): string => $c->craftSlug(),
            CraftSpecialization::cases()
        );

        $max = 0;
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $slug = strtolower(str_replace(' ', '-', $domain->getTitle()));
            if (!in_array($slug, $craftSlugs, true)) {
                continue;
            }
            $max = max($max, $domainExperience->getTotalExperience());
        }

        return $max;
    }
}
