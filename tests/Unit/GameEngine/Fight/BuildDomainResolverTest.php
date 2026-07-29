<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\GameEngine\Fight\BuildDomainResolver;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Ce que le build exprime (DOM-02).
 *
 * GAME_DOMAINS § 3 : « un domaine n'est actif en combat que si le build porte
 * une de ses sources ». La borne est **materielle** — personne ne lit un
 * interdit, le monde repond a ce qu'on porte. C'est ce qui separe ce systeme de
 * classes deguisees, et cela tient a ce que la reponse se **calcule** au lieu de
 * se declarer.
 */
class BuildDomainResolverTest extends TestCase
{
    private BuildDomainResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new BuildDomainResolver();
    }

    /**
     * Une ecole de sort s'exprime par la matiere qu'on sertit.
     */
    public function testASpellDomainNeedsAMateriaOfItsElement(): void
    {
        $player = $this->player([$this->gearWithMateria(Element::Fire)]);

        self::assertTrue($this->resolver->isActive($player, $this->domain('fire', CombatRegister::Spell)));
        self::assertFalse($this->resolver->isActive($player, $this->domain('water', CombatRegister::Spell)));
    }

    /**
     * Une ecole d'arme s'exprime par l'arme qu'on tient — et le registre de
     * l'arme est celui de **son** domaine, deja declare dans les fixtures.
     */
    public function testAWeaponDomainNeedsAWeaponOfItsRegister(): void
    {
        $player = $this->player([$this->weapon(CombatRegister::Melee)]);

        self::assertTrue($this->resolver->isActive($player, $this->domain('metal', CombatRegister::Melee)));
        self::assertFalse($this->resolver->isActive($player, $this->domain('air', CombatRegister::Ranged)));
    }

    /**
     * L'element d'une ecole d'arme ne se porte pas : c'est le registre qui la
     * qualifie. Exiger les deux rendrait le soldat dependant d'une matiere de
     * metal qui n'a rien a voir avec son epee.
     */
    public function testAWeaponDomainDoesNotNeedAMateriaOfItsElement(): void
    {
        $player = $this->player([$this->weapon(CombatRegister::Melee)]);

        self::assertTrue($this->resolver->isActive($player, $this->domain('dark', CombatRegister::Melee)));
    }

    /**
     * L'epee de bois du debutant n'appartient a aucune ecole, et n'en ouvre
     * aucune. Elle ne ferme rien non plus : l'attaque de base ne lit pas les
     * passifs (DOM-01).
     */
    public function testAWeaponWithoutADomainOpensNoSchool(): void
    {
        $player = $this->player([$this->weapon(null)]);

        self::assertFalse($this->resolver->isActive($player, $this->domain('metal', CombatRegister::Melee)));
    }

    /**
     * Un metier n'a pas de source de combat a porter.
     */
    public function testACraftDomainIsNeverActiveInCombat(): void
    {
        $player = $this->player([$this->weapon(CombatRegister::Melee), $this->gearWithMateria(Element::Fire)]);

        self::assertFalse($this->resolver->isActive($player, $this->domain('metal', null)));
    }

    /**
     * Ce qui n'est pas porte n'apporte rien.
     */
    public function testUnequippedGearCarriesNothing(): void
    {
        $stashed = $this->gearWithMateria(Element::Fire, equipped: false);

        self::assertSame([], $this->resolver->carriedElements($this->player([$stashed])));
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function domain(?string $element, ?CombatRegister $register): Domain&MockObject
    {
        $domain = $this->createMock(Domain::class);
        $domain->method('getElement')->willReturn($element);
        $domain->method('getRegister')->willReturn($register);

        return $domain;
    }

    private function gearWithMateria(Element $element, bool $equipped = true): PlayerItem&MockObject
    {
        $generic = $this->createMock(Item::class);
        $generic->method('getElement')->willReturn($element);

        $materia = $this->createMock(PlayerItem::class);
        $materia->method('isMateria')->willReturn(true);
        $materia->method('getGenericItem')->willReturn($generic);

        $slot = $this->createMock(Slot::class);
        $slot->method('getItemSet')->willReturn($materia);

        $host = $this->createMock(Item::class);
        $host->method('getGearLocation')->willReturn('chest');

        $piece = $this->createMock(PlayerItem::class);
        $piece->method('getGear')->willReturn($equipped ? 1 : 0);
        $piece->method('getGenericItem')->willReturn($host);
        $piece->method('getSlots')->willReturn(new ArrayCollection([$slot]));

        return $piece;
    }

    private function weapon(?CombatRegister $register): PlayerItem&MockObject
    {
        $generic = $this->createMock(Item::class);
        $generic->method('getGearLocation')->willReturn('main_weapon');
        $generic->method('getElement')->willReturn(Element::None);
        $generic->method('getDomain')->willReturn($register === null ? null : $this->domain('metal', $register));

        $piece = $this->createMock(PlayerItem::class);
        $piece->method('getGear')->willReturn(1);
        $piece->method('getGenericItem')->willReturn($generic);
        $piece->method('getSlots')->willReturn(new ArrayCollection([]));

        return $piece;
    }

    /**
     * @param list<PlayerItem&MockObject> $items
     */
    private function player(array $items): Player&MockObject
    {
        $inventory = $this->createMock(Inventory::class);
        $inventory->method('getItems')->willReturn(new ArrayCollection($items));

        $player = $this->createMock(Player::class);
        $player->method('getInventories')->willReturn(new ArrayCollection([$inventory]));

        return $player;
    }
}
