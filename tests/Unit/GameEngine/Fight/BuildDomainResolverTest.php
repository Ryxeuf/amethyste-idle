<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\App\Slot;
use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Enum\CombatRegister;
use App\Enum\Element;
use App\GameEngine\Fight\BuildDomainResolver;
use App\GameEngine\Gear\WornPieceReader;
use App\GameEngine\Progression\EquipmentPortCatalog;
use App\GameEngine\Progression\SynergyCalculator;
use App\Helper\GearHelper;
use App\Helper\PlayerHelper;
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
        $synergies = $this->createMock(SynergyCalculator::class);
        $synergies->method('getExpressionWidenings')->willReturn([]);

        // DOM-09 : le registre d'une arme vient de sa **famille de port**, plus
        // du domaine de l'objet — donc le resolveur lit l'echelle reelle.
        $catalog = new EquipmentPortCatalog(\dirname(__DIR__, 4));
        $this->resolver = new BuildDomainResolver(
            $synergies,
            new WornPieceReader($catalog, new GearHelper($this->createMock(PlayerHelper::class))),
            $catalog,
        );
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

    /**
     * Une arme, decrite par ce qui la rend portable (DOM-09).
     *
     * Le registre ne vient plus du domaine de l'objet mais de sa **famille**,
     * lue sur l'echelon de port qu'elle exige : une epee dit `melee`, un baton
     * dit `spell`, et une arme **sans echelon** — l'epee de bois du debutant —
     * n'apporte aucun registre.
     */
    private function weapon(?CombatRegister $register): PlayerItem&MockObject
    {
        $portSkill = match ($register) {
            CombatRegister::Melee => 'port-sword',
            CombatRegister::Ranged => 'port-bow',
            CombatRegister::Spell => 'port-staff',
            default => null,
        };

        $requirements = [];
        if ($portSkill !== null) {
            $skill = new Skill();
            $skill->setSlug($portSkill);
            $requirements[] = $skill;
        }

        $generic = $this->createMock(Item::class);
        $generic->method('getGearLocation')->willReturn('main_weapon');
        $generic->method('getElement')->willReturn(Element::None);
        $generic->method('getRequirements')->willReturn(new ArrayCollection($requirements));

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
