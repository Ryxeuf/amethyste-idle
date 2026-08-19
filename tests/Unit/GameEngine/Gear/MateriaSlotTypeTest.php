<?php

namespace App\Tests\Unit\GameEngine\Gear;

use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\CombatRegister;
use App\Enum\MateriaSlotType;
use App\Exception\ItemNotEquippedException;
use App\Exception\MateriaSlotTypeException;
use App\GameEngine\Gear\MateriaGearSetter;
use App\GameEngine\Gear\SlotAcceptanceWidener;
use App\Helper\GearHelper;
use App\Helper\PlayerItemHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Ce qu'un emplacement accepte (DOM-03).
 *
 * GAME_DOMAINS § 3 : « la robe porte des emplacements de sort ; la plaque, des
 * emplacements de technique ; le cuir, l'entre-deux ». Le typage est de la
 * **donnee** — la piece declare, et rien d'autre ne decide.
 *
 * Le garde-fou qui compte n'est pas le refus : c'est que le refus porte sur le
 * **sertissage** et jamais sur le port. Une piece qui interdirait de se vetir
 * serait une classe deguisee, et c'est precisement ce que le premier garde-fou
 * du § 3 interdit.
 */
class MateriaSlotTypeTest extends TestCase
{
    /**
     * Un emplacement libre accepte tout — c'est sa definition, pas une
     * tolerance.
     */
    public function testAFreeSocketAcceptsEverything(): void
    {
        self::assertTrue(MateriaSlotType::Free->accepts(MateriaSlotType::Spell));
        self::assertTrue(MateriaSlotType::Free->accepts(MateriaSlotType::Technique));
    }

    public function testATypedSocketOnlyAcceptsItsOwnKind(): void
    {
        self::assertTrue(MateriaSlotType::Spell->accepts(MateriaSlotType::Spell));
        self::assertFalse(MateriaSlotType::Spell->accepts(MateriaSlotType::Technique));
        self::assertTrue(MateriaSlotType::Technique->accepts(MateriaSlotType::Technique));
        self::assertFalse(MateriaSlotType::Technique->accepts(MateriaSlotType::Spell));
    }

    /**
     * Une piece qui ne dit rien accepte tout.
     *
     * C'est ce qui rend le typage **additif** : les 121 pieces livrees se
     * comportent exactement comme avant le jalon, et le plancher jour 1 tient
     * sans qu'on l'ecrive piece par piece.
     */
    public function testAnUntypedPieceKeepsItsSocketsFree(): void
    {
        $item = new Item();

        self::assertSame(MateriaSlotType::Free, $item->getMateriaSlotType());
    }

    /**
     * Le genre d'une materia se **derive** : celle qui accorde un sort est une
     * materia de sort.
     *
     * Le declarer aurait permis de la contredire — une materia dite
     * « technique » et porteuse d'un sort n'aurait eu aucun comportement defini.
     *
     * **ARC-02 — la derivation lit desormais le registre du geste.** Elle
     * lisait la seule *presence* d'un sort, ce qui rangeait en « technique »
     * tout ce qui n'en portait pas : une materia vide n'en est pourtant pas
     * une. Elle est maintenant `Free` — elle n'exige rien de la piece qui
     * l'accueille — et le genre suit le registre pour tout le reste.
     */
    public function testAMateriaKindIsDerivedFromWhatItGrants(): void
    {
        $spellMateria = new Item();
        $spellMateria->setSpell(new Spell());

        $technique = new Spell();
        $technique->setRegister(CombatRegister::Melee);
        $techniqueMateria = new Item();
        $techniqueMateria->setSpell($technique);

        self::assertSame(MateriaSlotType::Spell, $spellMateria->getMateriaKind());
        self::assertSame(MateriaSlotType::Technique, $techniqueMateria->getMateriaKind());
        self::assertSame(MateriaSlotType::Free, (new Item())->getMateriaKind());
    }

    // =====================================================================
    // Le sertissage
    // =====================================================================

    public function testAnIncompatibleMateriaIsRefusedAtSocketing(): void
    {
        $this->expectException(MateriaSlotTypeException::class);

        // Une piece a emplacements de technique, et une materia de sort : le
        // seul cas ou le typage mord aujourd'hui.
        $this->setter()->setMateria(
            $this->materia(spell: true),
            $this->socketOn(MateriaSlotType::Technique),
        );
    }

    public function testACompatibleMateriaGoesIn(): void
    {
        $this->expectNotToPerformAssertions();

        $this->setter()->setMateria(
            $this->materia(spell: true),
            $this->socketOn(MateriaSlotType::Spell),
        );
    }

    public function testAFreeSocketStillTakesAnything(): void
    {
        $this->expectNotToPerformAssertions();

        $this->setter()->setMateria(
            $this->materia(spell: true),
            $this->socketOn(MateriaSlotType::Free),
        );
    }

    /**
     * Le refus de sertir n'est pas un refus de porter.
     *
     * Le controle vient **apres** celui de l'equipement : la piece est portee,
     * elle n'accepte simplement pas cette matiere-la. Un test qui l'oublierait
     * laisserait passer une regression ou le typage deviendrait un gate de port.
     */
    public function testTheRefusalNeverConcernsWearingThePiece(): void
    {
        $this->expectException(ItemNotEquippedException::class);

        $setter = $this->setter(equipped: false);
        $setter->setMateria($this->materia(spell: true), $this->socketOn(MateriaSlotType::Technique));
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function setter(bool $equipped = true): MateriaGearSetter
    {
        $gearHelper = $this->createMock(GearHelper::class);
        $gearHelper->method('isEquipped')->willReturn($equipped);

        $playerItemHelper = $this->createMock(PlayerItemHelper::class);
        $playerItemHelper->method('canEquipMateria')->willReturn(true);

        $widener = $this->createMock(SlotAcceptanceWidener::class);
        $widener->method('widens')->willReturn(false);

        return new MateriaGearSetter($gearHelper, $this->createMock(EntityManagerInterface::class), $playerItemHelper, $this->createMock(EventDispatcherInterface::class), $widener);
    }

    private function materia(bool $spell): PlayerItem&MockObject
    {
        $generic = new Item();
        // ONB-12a : le sertissage s'annonce, et l'annonce porte le slug de la
        // materia. Une materia sans slug n'existe pas en jeu.
        $generic->setSlug('m1-sujet');
        if ($spell) {
            $generic->setSpell(new Spell());
        }

        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($generic);

        return $materia;
    }

    private function socketOn(MateriaSlotType $type): Slot&MockObject
    {
        $host = new Item();
        $host->setMateriaSlotType($type);

        $piece = $this->createMock(PlayerItem::class);
        $piece->method('getGenericItem')->willReturn($host);

        $slot = $this->createMock(Slot::class);
        $slot->method('getItem')->willReturn($piece);

        return $slot;
    }
}
