<?php

namespace App\GameEngine\Crafting;

use App\Entity\App\Player;
use App\Entity\App\PlayerCraftSpecialization;
use App\Enum\CraftSpecialization;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Le choix d'une branche terminale, arbre par arbre (DOM-04).
 *
 * **Ce que ce jalon a defait.** Le modele livre portait une specialisation
 * unique pour tout le personnage, et irreversible : devenir Forgeron fermait a
 * jamais la maitrise du Tanneur. C'est l'exclusivite *entre* arbres, que la
 * doctrine interdit — « interdire un arbre serait interdire un geste »
 * (GAME_DOMAINS § 1). Le renoncement se joue desormais **dans** l'arbre.
 *
 * **Le respec de specialisation est le seul respec payant du jeu** (§ 6). Le
 * respec de points ordinaire reste doux : ce qu'on paie n'est pas d'avoir
 * change d'avis sur un bonus, c'est d'avoir change d'identite. « Le forgeron
 * d'armes de la region est une personne, pas une case. »
 */
class CraftSpecializationService
{
    /**
     * Seuil d'XP de domaine requis pour debloquer le choix dans un arbre.
     *
     * Lu **par arbre** depuis DOM-04, la ou il l'etait sur le meilleur des
     * quatre : un joueur qui atteignait le seuil chez le forgeron pouvait se
     * declarer alchimiste sans avoir jamais touche a un mortier.
     */
    public const REQUIRED_DOMAIN_XP = 500;

    /**
     * Bonus additionnel de chance d'amelioration de qualite (en %) sur le craft
     * correspondant a la specialisation.
     */
    public const QUALITY_BONUS_CHANCE = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CraftBranchCatalog $branches,
        private readonly int $respecCost,
    ) {
    }

    /**
     * Les arbres qui offrent une specialisation.
     *
     * @return list<CraftSpecialization>
     */
    public function getAvailableSpecializations(): array
    {
        return $this->branches->specializableCrafts();
    }

    public function getRespecCost(): int
    {
        return $this->respecCost;
    }

    /**
     * Le joueur peut-il prendre une branche dans cet arbre ?
     *
     * @return array{ok: bool, reason: string}
     */
    public function canChoose(Player $player, CraftSpecialization $craft): array
    {
        if ($player->getCraftSpecializationFor($craft->value) !== null) {
            return [
                'ok' => false,
                'reason' => 'Vous avez deja pris une branche dans cet arbre. En changer demande un respec.',
            ];
        }

        if ($this->getDomainXp($player, $craft) < self::REQUIRED_DOMAIN_XP) {
            return [
                'ok' => false,
                'reason' => sprintf('Atteignez %d XP dans cet arbre pour y choisir une branche.', self::REQUIRED_DOMAIN_XP),
            ];
        }

        return ['ok' => true, 'reason' => ''];
    }

    /**
     * Prend une branche dans un arbre.
     *
     * @return array{success: bool, message: string}
     */
    public function choose(Player $player, CraftSpecialization $craft, string $branch): array
    {
        if (!$this->branches->hasBranch($craft, $branch)) {
            return ['success' => false, 'message' => 'Branche inconnue pour ce metier.'];
        }

        $check = $this->canChoose($player, $craft);
        if (!$check['ok']) {
            return ['success' => false, 'message' => $check['reason']];
        }

        $specialization = new PlayerCraftSpecialization($player, $craft, $branch);
        $player->addCraftSpecialization($specialization);
        $this->entityManager->persist($specialization);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf('Vous etes desormais %s.', (string) $this->branches->labelOf($craft, $branch)),
        ];
    }

    /**
     * Change de branche dans un arbre, contre paiement.
     *
     * Le seul respec payant du jeu. Le refus quand les gils manquent dit le
     * prix : un « impossible » sans chiffre laisserait croire a un verrou.
     *
     * @return array{success: bool, message: string}
     */
    public function respec(Player $player, CraftSpecialization $craft, string $branch): array
    {
        $current = $player->getCraftSpecializationFor($craft->value);
        if ($current === null) {
            return ['success' => false, 'message' => 'Vous n\'avez pas encore de branche dans cet arbre.'];
        }

        if (!$this->branches->hasBranch($craft, $branch)) {
            return ['success' => false, 'message' => 'Branche inconnue pour ce metier.'];
        }

        if ($current->getBranch() === $branch) {
            return ['success' => false, 'message' => 'C\'est deja votre branche.'];
        }

        if ($player->getGils() < $this->respecCost) {
            return [
                'success' => false,
                'message' => sprintf('Changer de branche coute %d gils. Il vous en manque %d.', $this->respecCost, $this->respecCost - $player->getGils()),
            ];
        }

        $player->setGils($player->getGils() - $this->respecCost);
        $current->setBranch($branch);
        $this->entityManager->flush();

        return [
            'success' => true,
            'message' => sprintf('Vous etes desormais %s.', (string) $this->branches->labelOf($craft, $branch)),
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
     * L'XP totale du joueur **dans cet arbre**.
     */
    private function getDomainXp(Player $player, CraftSpecialization $craft): int
    {
        foreach ($player->getDomainExperiences() as $domainExperience) {
            $domain = $domainExperience->getDomain();
            $slug = strtolower(str_replace(' ', '-', $domain->getTitle()));
            if ($slug === $craft->craftSlug()) {
                return $domainExperience->getTotalExperience();
            }
        }

        return 0;
    }
}
