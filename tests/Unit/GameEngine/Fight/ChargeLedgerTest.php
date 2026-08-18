<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\Game\Spell;
use App\GameEngine\Fight\ChargeLaw;
use App\GameEngine\Fight\ChargeLedger;
use PHPUnit\Framework\TestCase;

/**
 * Ou vit la charge, et ce qui la fait mourir (ARC-18e).
 *
 * Le garde-fou du canon tient dans une phrase — ***la charge meurt avec la
 * rencontre*** : une ressource qui persiste entre les combats double la
 * comptabilite de la journee (§ 9 septies) et transforme le jeu en gestion de
 * stock. Ce fichier verifie qu'elle n'a nulle part ou survivre.
 */
class ChargeLedgerTest extends TestCase
{
    private ChargeLedger $ledger;

    protected function setUp(): void
    {
        $this->ledger = new ChargeLedger();
    }

    /**
     * **Elle ne vit que dans les metadonnees du combat.**.
     *
     * Ni sur le joueur, ni en base a part : le jour ou la rencontre s'efface,
     * la charge s'efface avec, sans qu'on ait rien a nettoyer. C'est le
     * garde-fou tenu par le **rangement** plutot que par une routine — *une
     * remise a zero qu'il faut penser a appeler finit par etre oubliee*.
     */
    public function testItLivesNowhereButInTheEncounter(): void
    {
        $fight = new Fight();
        $player = $this->player(1);

        $this->ledger->apply($fight, $player, $this->spell(gain: 2));

        self::assertSame(2, $this->ledger->of($fight, $player));
        self::assertSame([ChargeLaw::METADATA_KEY => ['1' => 2]], $fight->getMetadata());

        // La rencontre suivante est un autre objet : rien ne se reporte.
        self::assertSame(0, $this->ledger->of(new Fight(), $player));
    }

    /**
     * Elle se construit, puis se depense.
     */
    public function testItBuildsThenSpends(): void
    {
        $fight = new Fight();
        $player = $this->player(1);
        $builder = $this->spell(gain: 2);
        $finisher = $this->spell(cost: 3);

        $this->ledger->apply($fight, $player, $builder);
        $this->ledger->apply($fight, $player, $builder);

        self::assertSame(4, $this->ledger->of($fight, $player));
        self::assertTrue($this->ledger->affords($fight, $player, $finisher));

        self::assertSame(1, $this->ledger->apply($fight, $player, $finisher));
    }

    /**
     * Un geste qu'on ne peut pas payer est refuse **avant** d'etre joue.
     *
     * La question se pose donc au ledger, pas apres coup : un geste refuse ne
     * doit rien couter, et surtout il ne doit pas se jouer en moins fort.
     */
    public function testAnUnaffordableGestureIsRefusedAndCostsNothing(): void
    {
        $fight = new Fight();
        $player = $this->player(1);
        $finisher = $this->spell(cost: 3);

        self::assertFalse($this->ledger->affords($fight, $player, $finisher));

        $this->ledger->apply($fight, $player, $this->spell(gain: 1));
        self::assertFalse($this->ledger->affords($fight, $player, $finisher));
        self::assertSame(1, $this->ledger->of($fight, $player), 'Le refus a preleve quelque chose.');
    }

    /**
     * En coop, le tour d'un joueur n'ecrit jamais la ligne d'un autre.
     */
    public function testEachPlayerHasHisOwnCounter(): void
    {
        $fight = new Fight();
        $one = $this->player(1);
        $two = $this->player(2);

        $this->ledger->apply($fight, $one, $this->spell(gain: 3));

        self::assertSame(3, $this->ledger->of($fight, $one));
        self::assertSame(0, $this->ledger->of($fight, $two));
    }

    /**
     * Un compteur retombe a zero **disparait**.
     *
     * Garder une ligne a zero laisserait un etat qui ressemble a une charge
     * sans en etre une, et c'est le genre d'etat qu'un lecteur finit par
     * croire.
     */
    public function testAnEmptyCounterLeavesNoTrace(): void
    {
        $fight = new Fight();
        $player = $this->player(1);

        $this->ledger->apply($fight, $player, $this->spell(gain: 2));
        $this->ledger->apply($fight, $player, $this->spell(cost: 2));

        self::assertSame(0, $this->ledger->of($fight, $player));
        self::assertSame([ChargeLaw::METADATA_KEY => []], $fight->getMetadata());
    }

    private function player(int $id): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }

    private function spell(int $gain = 0, int $cost = 0): Spell
    {
        $spell = new Spell();
        $spell->setChargeGain($gain);
        $spell->setChargeCost($cost);

        return $spell;
    }
}
