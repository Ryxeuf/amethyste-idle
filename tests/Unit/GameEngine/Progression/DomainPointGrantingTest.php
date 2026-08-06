<?php

namespace App\Tests\Unit\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\Game\Domain;
use App\GameEngine\Progression\CombatGestureLedger;
use App\GameEngine\Progression\DomainPointYield;
use PHPUnit\Framework\TestCase;

/**
 * La distribution des points de combat (ARC-06b).
 *
 * ARC-06a avait pose la table du gain sans canal : le combat ne rapportait
 * **aucun** point de domaine. Ce test tient les deux proprietes sans
 * lesquelles le canal ne vaut rien — *le reste ne se perd pas* et *un joueur
 * credite un seul arbre par rencontre*.
 */
class DomainPointGrantingTest extends TestCase
{
    /**
     * Le reste survit d'une rencontre a la suivante.
     *
     * C'est **la** raison d'etre du quart de point. Un compteur en points
     * entiers verrait quatre rencontres de palier 1 comme quatre arrondis a
     * zero : le joueur qui chasse a son palier ne progresserait jamais, et la
     * regle « on ne monte pas un arbre en tapant des rats » deviendrait « on
     * ne monte pas un arbre, point ».
     */
    public function testTheRemainderIsCarriedInsteadOfRounded(): void
    {
        $experience = new DomainExperience();

        // Trois rencontres de palier 1 : pas encore un point, rien de perdu.
        for ($i = 0; $i < 3; ++$i) {
            self::assertSame(0, $experience->addQuarters(DomainPointYield::quartersFor(1)));
        }
        self::assertSame(0, $experience->getTotalExperience());
        self::assertSame(3, $experience->getExperienceQuarters());

        // La quatrieme fait le point, et remet le reste a zero.
        self::assertSame(1, $experience->addQuarters(DomainPointYield::quartersFor(1)));
        self::assertSame(1, $experience->getTotalExperience());
        self::assertSame(0, $experience->getExperienceQuarters());
    }

    /**
     * Cent rencontres de palier 1 valent exactement vingt-cinq points.
     *
     * L'invariant que l'arrondi casserait : sur une longue serie, rien ne se
     * cree et rien ne se perd.
     */
    public function testNothingIsLostOverALongSeries(): void
    {
        $experience = new DomainExperience();
        for ($i = 0; $i < 100; ++$i) {
            $experience->addQuarters(DomainPointYield::quartersFor(1));
        }

        self::assertSame(25, $experience->getTotalExperience());
        self::assertSame(0, $experience->getExperienceQuarters());
    }

    /**
     * Le reste reste un reste : jamais quatre quarts en attente.
     */
    public function testTheCarryNeverHoldsAWholePoint(): void
    {
        $experience = new DomainExperience();
        foreach ([1, 2, 3, 4, 1, 4, 2] as $tier) {
            $experience->addQuarters(DomainPointYield::quartersFor(min(4, $tier)));
            self::assertLessThan(DomainPointYield::QUARTERS_PER_POINT, $experience->getExperienceQuarters());
            self::assertGreaterThanOrEqual(0, $experience->getExperienceQuarters());
        }
    }

    /**
     * Un gain nul ou negatif ne touche a rien.
     */
    public function testANonPositiveGainChangesNothing(): void
    {
        $experience = new DomainExperience();
        $experience->addQuarters(3);

        self::assertSame(0, $experience->addQuarters(0));
        self::assertSame(0, $experience->addQuarters(-8));
        self::assertSame(3, $experience->getExperienceQuarters());
        self::assertSame(0, $experience->getTotalExperience());
    }

    /**
     * Un joueur credite **un** arbre par rencontre, celui du dernier geste.
     *
     * La decision du 2026-08-06 refuse la multiplication : enchainer six
     * gestes de six cases ne rapporte pas six fois. Le registre l'obtient par
     * sa forme — une entree par joueur, ecrasee a chaque geste.
     */
    public function testAPlayerCreditsOneTreePerEncounter(): void
    {
        $ledger = new CombatGestureLedger();
        $fight = new Fight();
        $player = $this->playerWithId(7);

        $ledger->record($fight, $player, $this->domainWithId(11));
        $ledger->record($fight, $player, $this->domainWithId(12));
        $ledger->record($fight, $player, $this->domainWithId(13));

        self::assertSame(13, $ledger->caseFor($fight, $player));

        $cases = $fight->getMetadataValue(CombatGestureLedger::METADATA_KEY);
        self::assertCount(1, $cases);
    }

    /**
     * Un geste sans case efface la ligne au lieu de laisser la precedente.
     *
     * Sans cela, ouvrir sur un geste de feu puis finir a mains nues garderait
     * le credit du feu — un tour joue hors de toute ecole rapporterait a une
     * ecole.
     */
    public function testAGestureWithoutACaseClearsTheLine(): void
    {
        $ledger = new CombatGestureLedger();
        $fight = new Fight();
        $player = $this->playerWithId(7);

        $ledger->record($fight, $player, $this->domainWithId(11));
        $ledger->record($fight, $player, null);

        self::assertNull($ledger->caseFor($fight, $player));
    }

    /**
     * En coop, le tour d'un joueur n'ecrit jamais la ligne d'un autre.
     */
    public function testEachPlayerKeepsTheirOwnCase(): void
    {
        $ledger = new CombatGestureLedger();
        $fight = new Fight();
        $one = $this->playerWithId(7);
        $two = $this->playerWithId(8);

        $ledger->record($fight, $one, $this->domainWithId(11));
        $ledger->record($fight, $two, $this->domainWithId(22));

        self::assertSame(11, $ledger->caseFor($fight, $one));
        self::assertSame(22, $ledger->caseFor($fight, $two));
    }

    /**
     * Un combat ou personne n'a encore joue ne credite personne.
     */
    public function testAnUntouchedFightCreditsNobody(): void
    {
        $ledger = new CombatGestureLedger();

        self::assertNull($ledger->caseFor(new Fight(), $this->playerWithId(7)));
    }

    private function playerWithId(int $id): Player
    {
        $player = new Player();
        $reflection = new \ReflectionProperty(Player::class, 'id');
        $reflection->setValue($player, $id);

        return $player;
    }

    private function domainWithId(int $id): Domain
    {
        $domain = new Domain();
        $reflection = new \ReflectionProperty(Domain::class, 'id');
        $reflection->setValue($domain, $id);

        return $domain;
    }
}
