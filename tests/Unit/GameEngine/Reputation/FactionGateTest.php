<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Enum\ReputationTier;
use App\GameEngine\Reputation\FactionGate;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * La porte : ce qu'elle ouvre, et ce qu'elle laisse passer (FAC-09).
 */
class FactionGateTest extends TestCase
{
    /**
     * Une zone sans garde est ouverte. C'est la regle par defaut, et elle vaut
     * pour tout le monde sauf cinq zones : *la garde est opt-in, rien de ce qui
     * etait accessible ne se ferme*.
     */
    public function testAnUnguardedZoneIsOpenToEveryone(): void
    {
        self::assertTrue($this->gate(null)->isOpenFor(new Player(), new Zone()));
    }

    public function testAGuardedZoneOpensAtTheRequiredTierAndNotBefore(): void
    {
        $zone = $this->guarded('ombres', ReputationTier::Exalte);

        self::assertFalse($this->gate($this->faction('ombres'), ReputationTier::Revere->threshold())->isOpenFor(new Player(), $zone));
        self::assertTrue($this->gate($this->faction('ombres'), ReputationTier::Exalte->threshold())->isOpenFor(new Player(), $zone));
    }

    /**
     * Un joueur sans ligne de reputation chez cette maison est a zero, donc
     * dehors — et **pas** en erreur : on n'ouvre pas une porte par accident
     * d'implementation.
     */
    public function testAPlayerWhoNeverMetTheHouseStaysOutside(): void
    {
        self::assertFalse(
            $this->gate($this->faction('ombres'), null)->isOpenFor(new Player(), $this->guarded('ombres', ReputationTier::Exalte)),
        );
    }

    /**
     * **Une garde qui nomme une maison pas encore semee est inerte.**.
     *
     * Meme doctrine que la paire de tension declaree avant que la Fonderie
     * existe (FAC-01) : on ne ferme pas une porte au nom de quelqu'un qui n'est
     * pas la. Le contraire enfermerait un contenu derriere une faction qu'aucun
     * jeu de fixtures ne charge, et le defaut serait muet.
     */
    public function testAGateNamingAnUnseededHouseIsInert(): void
    {
        self::assertTrue(
            $this->gate(null)->isOpenFor(new Player(), $this->guarded('maison-a-venir', ReputationTier::Exalte)),
        );
    }

    private function guarded(string $faction, ReputationTier $tier): Zone
    {
        $zone = new Zone();
        $zone->setRequiredFaction($faction);
        $zone->setRequiredTier($tier);

        return $zone;
    }

    private function faction(string $slug): Faction
    {
        $faction = new Faction();
        $faction->setSlug($slug);
        $faction->setName($slug);

        return $faction;
    }

    private function gate(?Faction $faction, ?int $reputation = null): FactionGate
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($faction);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $playerFaction = null;
        if ($reputation !== null) {
            $playerFaction = $this->createMock(PlayerFaction::class);
            $playerFaction->method('getReputation')->willReturn($reputation);
        }

        $reputationManager = $this->createMock(ReputationManager::class);
        $reputationManager->method('getPlayerFaction')->willReturn($playerFaction);

        return new FactionGate($entityManager, $reputationManager);
    }
}
