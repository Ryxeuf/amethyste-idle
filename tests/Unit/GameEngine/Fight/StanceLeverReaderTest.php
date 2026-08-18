<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\StanceLeverReader;
use App\GameEngine\Progression\CombatLeverDefinitionException;
use App\GameEngine\Progression\CombatLeverDefinitionLoader;
use App\GameEngine\Progression\CombatLeverScale;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une posture a le droit d'ecrire (ARC-18b).
 *
 * `StatusEffect::levers` est une colonne JSON — elle accepte n'importe quoi.
 * Ce test tient le seul endroit qui refuse : *un vocabulaire n'est ferme que
 * s'il existe un endroit qui refuse*, la discipline de `SkillLeverReader`
 * appliquee aux postures.
 */
class StanceLeverReaderTest extends TestCase
{
    /**
     * Une posture equilibree se lit, telle quelle.
     */
    public function testABalancedStanceReads(): void
    {
        self::assertSame(
            ['power' => 6, 'guard' => -6],
            $this->reader()->pointsOf($this->stance(['power' => 6, 'guard' => -6]))
        );
    }

    /**
     * **Ce qui n'est pas une posture n'en a pas les pouvoirs.**.
     *
     * La question ne se pose donc jamais aux quatorze autres statuts livres, et
     * un `levers` ecrit par erreur sur un poison ne fait rien plutot que de
     * faire quelque chose en silence.
     */
    public function testOnlyAStanceCarriesLevers(): void
    {
        $poison = $this->stance(['power' => 6, 'guard' => -6]);
        $poison->setType(StatusEffect::TYPE_POISON);

        self::assertSame([], $this->reader()->pointsOf($poison));
    }

    /**
     * Un levier hors du vocabulaire est refuse a la lecture.
     */
    public function testAnUnknownLeverIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);
        $this->reader()->pointsOf($this->stance(['ferocity' => 6, 'guard' => -6]));
    }

    /**
     * Une ligne qui ne deplace rien est une intention qu'on croit avoir
     * exprimee.
     */
    public function testALeverThatMovesNothingIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);
        $this->reader()->pointsOf($this->stance(['power' => 0, 'guard' => -6]));
    }

    /**
     * **Le refus qui compte** : une posture qui donne sans rien reprendre.
     *
     * C'est celui qui empeche la forme de devenir un bouton qu'on presse au
     * tour 1 et qu'on n'a plus jamais a considerer.
     */
    public function testAStanceThatOnlyGivesIsRefused(): void
    {
        $this->expectException(CombatLeverDefinitionException::class);
        $this->reader()->pointsOf($this->stance(['power' => 6]));
    }

    /**
     * Elle ne deplace pas plus qu'un capstone.
     *
     * Le nœud le plus lourd du jeu vaut 14 points de budget (§ 6.3) ; une
     * posture s'y cale. Au-dela, elle ne serait plus une inflexion de la
     * rencontre mais un autre personnage — et le canon a un mot pour cela,
     * c'est la fourche, et elle se decide une fois.
     */
    public function testAStanceNeverMovesMoreThanACapstone(): void
    {
        // A la borne : accepte.
        self::assertSame(
            ['power' => StanceLeverReader::MAX_WEIGHT, 'guard' => -StanceLeverReader::MAX_WEIGHT],
            $this->reader()->pointsOf($this->stance(['power' => StanceLeverReader::MAX_WEIGHT, 'guard' => -StanceLeverReader::MAX_WEIGHT]))
        );

        $this->expectException(CombatLeverDefinitionException::class);
        $this->reader()->pointsOf($this->stance([
            'power' => StanceLeverReader::MAX_WEIGHT + 1,
            'guard' => -(StanceLeverReader::MAX_WEIGHT + 1),
        ]));
    }

    /**
     * @param array<string, mixed> $levers
     */
    private function stance(array $levers): StatusEffect
    {
        $effect = new StatusEffect();
        $effect->setSlug('braced');
        $effect->setType(StatusEffect::TYPE_STANCE);
        $effect->setLevers($levers);

        return $effect;
    }

    private function reader(): StanceLeverReader
    {
        return new StanceLeverReader(
            new CombatLeverScale(new CombatLeverDefinitionLoader(\dirname(__DIR__, 4))),
            $this->createMock(EntityManagerInterface::class),
        );
    }
}
