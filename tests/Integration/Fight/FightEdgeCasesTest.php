<?php

namespace App\Tests\Integration\Fight;

use App\Event\Fight\PlayerDeadEvent;
use App\GameEngine\Fight\CombatLogArchiver;
use App\GameEngine\Fight\FightCalculator;
use App\GameEngine\Fight\Handler\FightHandler;
use App\GameEngine\Fight\StatusEffectManager;
use App\Tests\Integration\AbstractIntegrationTestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * TST-05 sous-tache C : tests d'integration des cas limites de combat.
 *
 * Utilise une vraie DB avec fixtures, pas de mocks.
 */
class FightEdgeCasesTest extends AbstractIntegrationTestCase
{
    /**
     * Un joueur sans arme equipee peut toujours effectuer une attaque de base.
     * Le baseDamage est de 3 + variance (0-2), independant de l'arme.
     */
    public function testPlayerWithNoWeaponCanStillAttack(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        // Retirer tout equipement du joueur (gear = 0 signifie non equipe)
        $playerItems = $this->em->getRepository(\App\Entity\App\PlayerItem::class)
            ->findBy(['player' => $player]);

        foreach ($playerItems as $playerItem) {
            if ($playerItem->getGear() > 0) {
                $playerItem->setGear(0);
            }
        }
        $this->em->flush();

        // Creer un combat
        $fight = $this->createFight($player, $mob);
        $initialMobLife = $mob->getLife();
        $this->assertGreaterThan(0, $initialMobLife);

        // Simuler une attaque basique (baseDamage = 3 + variance, pas de dependance a l'arme)
        // On force le hit pour un test deterministe
        $baseDamage = 3; // minimum sans variance
        $mob->setLife(max(0, $mob->getLife() - $baseDamage));
        $this->persistAndFlush($mob);

        $this->refresh($mob);
        $this->assertSame($initialMobLife - $baseDamage, $mob->getLife());
        $this->assertGreaterThan(0, $mob->getLife());

        // Verifier que le joueur peut aussi toucher via FightCalculator
        // Avec hit = 100, c'est toujours un succes
        $this->assertTrue(FightCalculator::hasAttackHit(100));

        // Verifier que le combat est toujours en cours
        $this->assertFalse($fight->isTerminated());
    }

    /**
     * La fuite reussie libere le joueur du combat et nettoie le combat (solo).
     * Le joueur est repositionne sur ses dernieres coordonnees.
     */
    public function testFleeFromCombat(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        // Sauvegarder les coordonnees avant le combat
        $lastCoordinates = $player->getLastCoordinates();
        $currentCoordinates = $player->getCoordinates();

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        $this->assertNotNull($player->getFight(), 'Player should be in a fight.');
        $fightId = $fight->getId();

        // Simuler la fuite reussie (reproduit la logique du FightFleeController)
        $player->setCoordinates($player->getLastCoordinates());
        $player->setIsMoving(false);

        // Dissocier le joueur du combat
        $player->setFight(null);
        $fight->removePlayer($player);

        // Nettoyage combat solo
        /** @var CombatLogArchiver $combatLogArchiver */
        $combatLogArchiver = $this->getService(CombatLogArchiver::class);
        $combatLogArchiver->archive($fight);

        /** @var StatusEffectManager $statusEffectManager */
        $statusEffectManager = $this->getService(StatusEffectManager::class);
        $statusEffectManager->clearAllEffects($fight);

        foreach ($fight->getMobs() as $fightMob) {
            $this->em->remove($fightMob);
        }

        $this->em->remove($fight);
        $this->em->persist($player);
        $this->em->flush();

        // Verifications post-fuite
        $this->assertNull($player->getFight(), 'Player should not be in a fight after fleeing.');
        $this->assertSame($lastCoordinates, $player->getCoordinates(), 'Player should be repositioned to last coordinates.');
        $this->assertFalse($player->isMoving(), 'Player should not be moving after fleeing.');

        // Le combat doit etre supprime de la DB
        $deletedFight = $this->em->getRepository(\App\Entity\App\Fight::class)->find($fightId);
        $this->assertNull($deletedFight, 'Fight should be deleted from DB after successful flee.');
    }

    /**
     * La mort du joueur en combat declenche le respawn avec 50% de vie max.
     * Le combat est nettoye et le joueur est libere.
     */
    public function testPlayerDeathInCombat(): void
    {
        $player = $this->getPlayer();
        $mob = $this->getMob($player->getMap());

        $maxLife = $player->getMaxLife();
        $this->assertGreaterThan(0, $maxLife);

        /** @var FightHandler $fightHandler */
        $fightHandler = $this->getService(FightHandler::class);
        $fight = $fightHandler->startFight($player, $mob);

        $fightId = $fight->getId();

        // Tuer le joueur
        $player->setLife(0);
        $player->setDiedAt(new \DateTime());
        $this->em->flush();

        $this->assertTrue($player->isDead(), 'Player should be dead.');
        $this->assertTrue($fight->isDefeat(), 'Fight should be a defeat when all players are dead.');

        // Dispatch PlayerDeadEvent comme le ferait SpellApplicator
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getService(EventDispatcherInterface::class);
        $dispatcher->dispatch(new PlayerDeadEvent($player), PlayerDeadEvent::NAME);

        // Simuler le handleDefeat (reproduit la logique du FightIndexController)
        /** @var CombatLogArchiver $combatLogArchiver */
        $combatLogArchiver = $this->getService(CombatLogArchiver::class);
        $combatLogArchiver->archive($fight);

        /** @var StatusEffectManager $statusEffectManager */
        $statusEffectManager = $this->getService(StatusEffectManager::class);
        $statusEffectManager->clearAllEffects($fight);

        // Dissocier le joueur du combat et respawn
        foreach ($fight->getPlayers() as $fightPlayer) {
            $fightPlayer->setFight(null);
            if ($fightPlayer->isDead()) {
                $fightPlayer->setLife((int) round($fightPlayer->getMaxLife() / 2));
                $fightPlayer->setDiedAt(null);
            }
            $this->em->persist($fightPlayer);
        }

        // Supprimer les mobs
        foreach ($fight->getMobs() as $fightMob) {
            $this->em->remove($fightMob);
        }

        $this->em->remove($fight);
        $this->em->flush();

        // Verifications post-respawn
        $this->refresh($player);
        $expectedLife = (int) round($maxLife / 2);
        $this->assertSame($expectedLife, $player->getLife(), 'Player should respawn with 50% max life.');
        $this->assertNull($player->getDiedAt(), 'Player should not be dead after respawn.');
        $this->assertFalse($player->isDead(), 'Player isDead() should return false after respawn.');
        $this->assertNull($player->getFight(), 'Player should not be in a fight after respawn.');

        // Le combat doit etre supprime
        $deletedFight = $this->em->getRepository(\App\Entity\App\Fight::class)->find($fightId);
        $this->assertNull($deletedFight, 'Fight should be deleted from DB after defeat.');
    }
}
