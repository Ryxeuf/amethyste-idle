<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\GameEngine\Balance\VitalityRegen;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Regeneration des PV hors combat (ZON-12).
 *
 * Deuxieme regulateur du pivot PBBG (docs/PIVOT_PBBG.md, docs/BALANCE.md
 * section 8) : l'energie limite les tentatives, les PV font payer les echecs.
 * Sortir affaibli d'un combat impose donc d'attendre la regen ou de consommer
 * des soins (objets/sorts, inchanges) avant de repartir a pleine puissance.
 *
 * Regeneration paresseuse en temps reel, calquee sur ActionEnergyManager :
 * calculee a la lecture, aucun cron par joueur. L'ancre `lifeUpdatedAt` est
 * reinitialisee a la sortie de chaque combat (cf. anchor()), si bien que le
 * temps ecoule n'est compte qu'a partir du moment ou le joueur quitte le
 * combat blesse — jamais depuis un plein anterieur au combat.
 *
 * Curseur d'equilibrage (table `parameter`) :
 *  - `zone.life.regen_seconds` : secondes par point de vie regenere (defaut 12).
 */
class LifeRegenManager
{
    public const DEFAULT_REGEN_SECONDS = 12;
    public const PARAM_REGEN_SECONDS = 'zone.life.regen_seconds';

    private ?int $regenSecondsCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Applique la regeneration des PV due depuis la derniere ancre. Ne fait
     * rien en combat (les PV y sont geres par le combat), ni pour un joueur
     * mort (il passe par le respawn, pas par la regen), ni au plein.
     * Retourne le nombre de PV regeneres.
     */
    public function refresh(Player $player, bool $flush = false): int
    {
        // En combat : les PV sont geres par le combat, pas de regen.
        if (null !== $player->getFight()) {
            return 0;
        }

        // Mort : le respawn s'en charge, pas la regen hors combat.
        if ($player->isDead()) {
            return 0;
        }

        $now = new \DateTimeImmutable();

        // MJ : sortir d'un combat blesse ne doit pas l'immobiliser. Le plein est
        // refait des la sortie de combat, sans attendre la regen. La mort reste
        // exclue plus haut : elle passe par le respawn, pour le MJ comme pour
        // les autres — le plein revient au refresh suivant.
        if ($player->isGameMaster()) {
            $granted = max(0, $player->getMaxLife() - $player->getLife());
            $player->setLife($player->getMaxLife());
            $player->setLifeUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return $granted;
        }

        $updatedAt = $player->getLifeUpdatedAt();

        if (null === $updatedAt) {
            $player->setLifeUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return 0;
        }

        if ($player->getLife() >= $player->getMaxLife()) {
            // Plein : le timer repartira a la prochaine sortie de combat blessee.
            $player->setLifeUpdatedAt($now);
            if ($flush) {
                $this->entityManager->flush();
            }

            return 0;
        }

        $regenSeconds = $this->getRegenSeconds();
        // ARC-20c — **la regeneration est proportionnelle a la barre.** Elle
        // valait 12 secondes par point en absolu, ce qui etait tenable quand la
        // barre valait 20 PV et que rien ne la faisait monter ; le Socle la
        // porte a 880 au palier 4, et le retour a plein passerait de 19 minutes
        // a 2 h 56. L'invariant : *le temps de retour a plein ne depend pas du
        // palier* (`VitalityRegen`).
        $elapsed = $now->getTimestamp() - $updatedAt->getTimestamp();
        $bar = $player->getMaxLife();
        $points = VitalityRegen::pointsFor(max(0, $elapsed), $bar, $regenSeconds);
        if (0 === $points) {
            return 0;
        }

        $missing = $bar - $player->getLife();
        $granted = min($points, $missing);
        $player->setLife($player->getLife() + $granted);

        if ($granted >= $missing) {
            // Plein atteint : reliquat sans objet.
            $player->setLifeUpdatedAt($now);
        } else {
            // Reliquat de temps conserve : l'ancre avance du temps que les
            // points credites ont reellement coute, jamais d'un multiple du
            // curseur — sinon le reliquat derive a chaque passage.
            $player->setLifeUpdatedAt($updatedAt->modify(sprintf('+%d seconds', VitalityRegen::secondsFor($points, $bar, $regenSeconds))));
        }

        if ($flush) {
            $this->entityManager->flush();
        }

        return $granted;
    }

    /**
     * Ancre la regeneration a maintenant : a appeler a la sortie de chaque
     * combat (victoire, fuite, defaite/respawn). Sans cela, un joueur plein
     * avant combat guerirait instantanement en sortie (temps ecoule depuis le
     * dernier plein compte comme regen).
     */
    public function anchor(Player $player): void
    {
        $player->setLifeUpdatedAt(new \DateTimeImmutable());
    }

    /**
     * Secondes restantes avant le prochain point de vie (null si plein, mort
     * ou en combat).
     */
    public function secondsUntilNextPoint(Player $player): ?int
    {
        if (null !== $player->getFight() || $player->isDead() || $player->isGameMaster()) {
            return null;
        }

        if ($player->getLife() >= $player->getMaxLife()) {
            return null;
        }

        $updatedAt = $player->getLifeUpdatedAt();
        if (null === $updatedAt) {
            return $this->getRegenSeconds();
        }

        $elapsed = time() - $updatedAt->getTimestamp();
        $step = VitalityRegen::secondsPerPoint($player->getMaxLife(), $this->getRegenSeconds());

        return max(0, $step - ($elapsed % $step));
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

        $missing = $player->getMaxLife() - $player->getLife();
        if ($missing <= 0) {
            return null;
        }

        $step = VitalityRegen::secondsPerPoint($player->getMaxLife(), $this->getRegenSeconds());
        $next = $this->secondsUntilNextPoint($player) ?? $step;

        return $next + ($missing - 1) * $step;
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
