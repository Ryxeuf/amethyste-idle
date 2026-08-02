<?php

namespace App\Tests\Unit\GameEngine\Dungeon;

use App\Entity\App\Player;
use App\Entity\Game\Spell;
use App\GameEngine\Dungeon\DungeonActionResolver;
use App\GameEngine\Fight\CombatCapacityResolver;
use App\GameEngine\Fight\CombatSkillResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * DON-02 — l'action d'un membre est celle de son build : le geste de l'arme
 * (ou les mains nues) plus les passifs, ou un sort de materia sertie avec le
 * bonus d'accord d'element. Avant : `max(1, $player->getHit())`, et deux
 * builds finis faisaient le meme donjon.
 */
class DungeonActionResolverTest extends TestCase
{
    private CombatCapacityResolver&MockObject $capacities;
    private CombatSkillResolver&MockObject $skills;
    private DungeonActionResolver $resolver;

    /** @var array{damage: int, heal: int, hit: int, critical: int, life: int} */
    public array $bonuses = ['damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0];

    protected function setUp(): void
    {
        $this->capacities = $this->createMock(CombatCapacityResolver::class);
        $this->skills = $this->createMock(CombatSkillResolver::class);
        $this->skills->method('getCombatBonuses')->willReturnCallback(fn (): array => $this->bonuses);

        $this->resolver = new DungeonActionResolver($this->capacities, $this->skills);
    }

    private function spell(int $damage): Spell
    {
        $spell = new Spell();
        $spell->setDamage($damage);

        return $spell;
    }

    /**
     * Mains nues, sans passif : le plancher — 1 degat, jamais zero (ONB-20a :
     * aucun chemin de combat n'echoue faute d'arme).
     */
    public function testBareHandsFloor(): void
    {
        $action = $this->resolver->resolve(new Player());

        $this->assertSame(['damage' => 1, 'spellSlug' => null], $action);
    }

    /**
     * Les passifs des arbres entrent dans le calcul : le meme geste frappe
     * plus fort chez qui a paye ses nœuds de degats.
     */
    public function testSkillPassivesRaiseTheDamage(): void
    {
        $this->bonuses['damage'] = 7;

        $action = $this->resolver->resolve(new Player());

        $this->assertSame(8, $action['damage']); // 1 (mains nues) + 7 de passifs
    }

    /**
     * Un sort de materia sertie et deverrouillee applique son degat, les
     * passifs, et le bonus d'accord d'element de l'emplacement.
     */
    public function testAMateriaSpellUsesItsDamageAndElementMatch(): void
    {
        $this->bonuses['damage'] = 3;
        $this->capacities->method('findMateriaSpell')->willReturn([
            'spell' => $this->spell(12),
            'materia' => $this->createMock(\App\Entity\App\PlayerItem::class),
            'slot' => $this->createMock(\App\Entity\App\Slot::class),
            'elementMatch' => true,
            'linkedBonus' => false,
            'locked' => false,
        ]);
        $this->capacities->method('getElementMatchDamageMultiplier')->willReturn(1.25);

        $action = $this->resolver->resolve(new Player(), 'fire-ball');

        $this->assertSame(19, $action['damage']); // round((12 + 3) * 1.25)
        $this->assertSame('fire-ball', $action['spellSlug']);
    }

    /**
     * Un sort verrouille (accord non appris) retombe sur l'attaque de base
     * plutot que d'echouer — la borne est ce qu'on porte, jamais un mur.
     */
    public function testALockedSpellFallsBackToTheBaseAttack(): void
    {
        $this->capacities->method('findMateriaSpell')->willReturn([
            'spell' => $this->spell(12),
            'materia' => $this->createMock(\App\Entity\App\PlayerItem::class),
            'slot' => $this->createMock(\App\Entity\App\Slot::class),
            'elementMatch' => false,
            'linkedBonus' => false,
            'locked' => true,
        ]);

        $action = $this->resolver->resolve(new Player(), 'fire-ball');

        $this->assertSame(['damage' => 1, 'spellSlug' => null], $action);
    }
}
