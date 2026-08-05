<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regeneration des PM hors combat (ARC-04a).
 *
 * **Le defaut.** Les PM — `Player::energy`, la ressource du registre des sorts,
 * distincte de l'energie d'action — ne revenaient que d'une facon : en lancant
 * un sort, qui en rend 5 % du maximum. Un lanceur a sec devait donc **depenser
 * ce qu'il cherchait a recuperer**, et hors combat rien ne remontait jamais.
 * Les PV, eux, se regenerent en temps reel depuis ZON-12.
 *
 * **La symetrie que ce jalon retablit** (GAME_ARCHETYPES §9 septies) : *les PV
 * paient les coups recus, les PM paient les gestes faits, et les deux se
 * rechargent en temps reel*. Sans elle, la fonction d'entretien joue trois fois
 * plus de contenu que les autres dans une journee — mesure du canon —, parce
 * qu'elle est la seule a ne rien devoir attendre.
 *
 * Mecanique **calquee sur `LifeRegenManager`**, volontairement : paresseuse,
 * calculee a la lecture, aucun cron par joueur, ancre remise a la sortie de
 * chaque combat. Deux regulateurs qui se comportent pareil sont deux
 * regulateurs qu'on apprend une fois.
 *
 * Curseur d'equilibrage (table `parameter`) :
 *  - `zone.mana.regen_seconds` : secondes par point regenere (defaut 6).
 *
 * Le defaut est **la moitie de celui des PV** (12 s), repere du canon : un pool
 * de PM se vide en un combat quand une barre de vie tient plusieurs rencontres.
 * GAME_ARCHETYPES §0.2 previent qu'aucun de ses nombres n'est definitif — d'ou
 * le parametre, qui se deplace sans toucher au code.
 */
class ManaRegenManager
{
    public const DEFAULT_REGEN_SECONDS = 6;
    public const PARAM_REGEN_SECONDS = 'zone.mana.regen_seconds';

    private ?int $regenSecondsCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Applique la regeneration des PM due depuis la derniere ancre.
     *
     * Ne fait rien en combat — les PM y sont geres par le combat, qui en rend
     * une part a chaque tour —, ni pour un joueur mort, ni au plein. Retourne
     * le nombre de points regeneres.
     */
    public function refresh(Player $player, bool $flush = false): int
    {
        if (null !== $player->getFight()) {
            return 0;
        }

        if ($player->isDead()) {
            return 0;
        }

        $now = new \DateTimeImmutable();

        // MJ : plein immediat, comme pour les PV.
        if ($player->isGameMaster()) {
            $granted = max(0, $player->getMaxEnergy() - $player->getEnergy());
            $player->setEnergy($player->getMaxEnergy());
            $player->setEnergyUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return $granted;
        }

        $updatedAt = $player->getEnergyUpdatedAt();

        if (null === $updatedAt) {
            $player->setEnergyUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return 0;
        }

        if ($player->getEnergy() >= $player->getMaxEnergy()) {
            $player->setEnergyUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return 0;
        }

        $regenSeconds = $this->getRegenSeconds();
        $elapsed = $now->getTimestamp() - $updatedAt->getTimestamp();
        $points = intdiv(max(0, $elapsed), $regenSeconds);
        if (0 === $points) {
            return 0;
        }

        $missing = $player->getMaxEnergy() - $player->getEnergy();
        $granted = min($points, $missing);
        $player->setEnergy($player->getEnergy() + $granted);

        if ($granted >= $missing) {
            $player->setEnergyUpdatedAt($now);
        } else {
            // Reliquat de temps conserve : l'ancre avance par pas entiers.
            $player->setEnergyUpdatedAt($updatedAt->modify(sprintf('+%d seconds', $points * $regenSeconds)));
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return $granted;
    }

    /**
     * Ancre la regeneration a maintenant : a appeler a la sortie de chaque
     * combat.
     *
     * Sans cela, un joueur entre plein en combat en ressortirait plein : le
     * temps ecoule depuis son dernier plein compterait comme de la regen, et
     * vider ses PM ne couterait rien.
     */
    public function anchor(Player $player): void
    {
        $player->setEnergyUpdatedAt(new \DateTimeImmutable());
    }

    /**
     * Secondes restantes avant le prochain point (null si plein, mort ou en
     * combat).
     */
    public function secondsUntilNextPoint(Player $player): ?int
    {
        if (null !== $player->getFight() || $player->isDead() || $player->isGameMaster()) {
            return null;
        }

        if ($player->getEnergy() >= $player->getMaxEnergy()) {
            return null;
        }

        $updatedAt = $player->getEnergyUpdatedAt();
        if (null === $updatedAt) {
            return $this->getRegenSeconds();
        }

        $elapsed = time() - $updatedAt->getTimestamp();
        $regenSeconds = $this->getRegenSeconds();

        return max(0, $regenSeconds - ($elapsed % $regenSeconds));
    }

    /**
     * Secondes restantes avant le plein (null si deja plein, mort ou en
     * combat).
     */
    public function secondsUntilFull(Player $player): ?int
    {
        if (null !== $player->getFight() || $player->isDead() || $player->isGameMaster()) {
            return null;
        }

        $missing = $player->getMaxEnergy() - $player->getEnergy();
        if ($missing <= 0) {
            return null;
        }

        $next = $this->secondsUntilNextPoint($player) ?? $this->getRegenSeconds();

        return $next + ($missing - 1) * $this->getRegenSeconds();
    }

    public function getRegenSeconds(): int
    {
        if (null !== $this->regenSecondsCache) {
            return $this->regenSecondsCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_REGEN_SECONDS]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_REGEN_SECONDS;

        return $this->regenSecondsCache = $value > 0 ? $value : self::DEFAULT_REGEN_SECONDS;
    }
}
