<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Energie d'action PBBG (ZON-07).
 *
 * Principe directeur (docs/PIVOT_PBBG.md) : l'energie gate l'acces aux
 * rencontres, JAMAIS le combat lui-meme. Regeneration paresseuse en temps
 * reel : calculee a la lecture, aucun cron par joueur.
 *
 * Curseurs d'equilibrage (docs/BALANCE.md) via la table `parameter` :
 *  - `zone.energy.regen_seconds` : secondes par point regenere (defaut 360).
 */
class ActionEnergyManager
{
    public const DEFAULT_REGEN_SECONDS = 360;
    public const PARAM_REGEN_SECONDS = 'zone.energy.regen_seconds';

    private ?int $regenSecondsCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Applique la regeneration due depuis le dernier calcul. Retourne le
     * nombre de points regeneres. Le reliquat de temps est conserve (le
     * curseur `actionEnergyUpdatedAt` avance par pas entiers de regen).
     */
    public function refresh(Player $player, bool $flush = false): int
    {
        $now = new \DateTimeImmutable();
        $updatedAt = $player->getActionEnergyUpdatedAt();

        if (null === $updatedAt) {
            $player->setActionEnergyUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return 0;
        }

        if ($player->getActionEnergy() >= $player->getMaxActionEnergy()) {
            // Plein : le timer repartira a la prochaine depense.
            $player->setActionEnergyUpdatedAt($now);
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

        $missing = $player->getMaxActionEnergy() - $player->getActionEnergy();
        $granted = min($points, $missing);
        $player->setActionEnergy($player->getActionEnergy() + $granted);

        if ($granted >= $missing) {
            // Plein atteint : reliquat sans objet.
            $player->setActionEnergyUpdatedAt($now);
        } else {
            $player->setActionEnergyUpdatedAt($updatedAt->modify(sprintf('+%d seconds', $points * $regenSeconds)));
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return $granted;
    }

    /**
     * Depense de l'energie d'action (apres regeneration due).
     *
     * @throws NotEnoughActionEnergyException
     */
    public function spend(Player $player, int $cost, bool $flush = true): void
    {
        if ($cost < 0) {
            throw new \InvalidArgumentException('Energy cost cannot be negative.');
        }

        $this->refresh($player);

        if ($player->getActionEnergy() < $cost) {
            throw new NotEnoughActionEnergyException('game.zone.energy.error.not_enough');
        }

        $wasFull = $player->getActionEnergy() >= $player->getMaxActionEnergy();
        $player->setActionEnergy($player->getActionEnergy() - $cost);
        if ($wasFull) {
            // Le timer de regen demarre a la premiere depense depuis le plein.
            $player->setActionEnergyUpdatedAt(new \DateTimeImmutable());
        }

        // FOY-17 — c'est ici, et nulle part ailleurs, que se mesure l'activite.
        // `spend()` est le passage oblige de toute action qui pese sur le monde
        // (explorer, chasser, recolter, rejoindre un evenement) ; se connecter
        // n'y passe pas, et c'est exactement ce qu'on veut : on compte la
        // charge, pas les tetes (BALANCE § 22.5).
        //
        // Une depense nulle ne vaut pas activite : elle ne pese sur rien.
        if ($cost > 0) {
            $player->setLastActivityAt(new \DateTimeImmutable());
            $player->addActionEnergySpent($cost);
        }

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Secondes restantes avant le prochain point (null si plein).
     */
    public function secondsUntilNextPoint(Player $player): ?int
    {
        if ($player->getActionEnergy() >= $player->getMaxActionEnergy()) {
            return null;
        }

        $updatedAt = $player->getActionEnergyUpdatedAt();
        if (null === $updatedAt) {
            return $this->getRegenSeconds();
        }

        $elapsed = time() - $updatedAt->getTimestamp();
        $regenSeconds = $this->getRegenSeconds();

        return max(0, $regenSeconds - ($elapsed % $regenSeconds));
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
