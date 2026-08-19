<?php

namespace App\Tests\Unit\GameEngine\Gear;

use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\Game\Domain;
use App\Entity\Game\DomainSynergy;
use App\Entity\Game\Item;
use App\Entity\Game\Skill;
use App\Entity\Game\Spell;
use App\GameEngine\Gear\SlotAcceptanceWidener;
use App\GameEngine\Progression\SynergyCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'un emplacement accepte en plus (ARC-16b).
 *
 * `slot_acceptance` : la paire suffit — une materia dont le geste est ouvert
 * par l'une des deux ecoles se sertit dans un emplacement qui l'aurait refusee
 * par son genre. *De la souplesse, jamais de la puissance.*
 */
class SlotAcceptanceWidenerTest extends TestCase
{
    public function testAMateriaOfThePairedSchoolIsAccepted(): void
    {
        $healer = $this->domainUnlocking('healing-wave');

        self::assertTrue(
            $this->widener([$this->pair($healer, $this->domainUnlocking('holy-light'))])
                ->widens($this->player(), $this->materia('healing-wave')),
        );
    }

    public function testAMateriaOfAThirdSchoolStaysRefused(): void
    {
        $pair = $this->pair($this->domainUnlocking('healing-wave'), $this->domainUnlocking('holy-light'));

        self::assertFalse($this->widener([$pair])->widens($this->player(), $this->materia('fireball')));
    }

    public function testWithoutAnActiveAccointanceNothingWidens(): void
    {
        self::assertFalse($this->widener([])->widens($this->player(), $this->materia('healing-wave')));
    }

    /**
     * @param list<DomainSynergy> $active
     */
    private function widener(array $active): SlotAcceptanceWidener
    {
        $synergyCalculator = $this->createMock(SynergyCalculator::class);
        $synergyCalculator->method('activeOfForm')->willReturn($active);

        return new SlotAcceptanceWidener($synergyCalculator);
    }

    private function pair(Domain $a, Domain $b): DomainSynergy
    {
        $synergy = new DomainSynergy();
        $synergy->setDomainA($a);
        $synergy->setDomainB($b);
        $synergy->setName('Liturgie');
        $synergy->setDescription('test');

        return $synergy;
    }

    private function domainUnlocking(string $spellSlug): Domain&MockObject
    {
        $skill = new Skill();
        $skill->setSlug('unlock-' . $spellSlug);
        $skill->setActions(['materia' => ['unlock' => $spellSlug]]);

        $domain = $this->createMock(Domain::class);
        $domain->method('getSkills')->willReturn(new ArrayCollection([$skill]));

        return $domain;
    }

    private function materia(string $spellSlug): PlayerItem
    {
        $spell = new Spell();
        $spell->setSlug($spellSlug);

        $generic = new Item();
        $generic->setSlug('m1-' . $spellSlug);
        $generic->setSpell($spell);

        $item = new PlayerItem();
        $item->setGenericItem($generic);

        return $item;
    }

    private function player(): Player
    {
        return $this->createMock(Player::class);
    }
}
