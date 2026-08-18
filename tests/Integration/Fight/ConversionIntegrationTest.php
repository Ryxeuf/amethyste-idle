<?php

namespace App\Tests\Integration\Fight;

use App\Entity\Game\Spell;
use App\GameEngine\Fight\CombatSkillResolver;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La conversion, de bout en bout (ARC-18c).
 *
 * Une seule chose compte vraiment ici : **elle paie un geste qu'on ne pourrait
 * plus payer**. C'est le defaut qu'elle repare — le pyromancien tombe en panne
 * de PM au tour 8 alors qu'il lui reste des points de vie —, et si la
 * conversion se resolvait apres le controle de cout, elle exigerait d'avoir
 * deja les PM qu'elle sert a obtenir.
 */
class ConversionIntegrationTest extends AbstractIntegrationTestCase
{
    private CombatSkillResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = $this->getService(CombatSkillResolver::class);
    }

    /**
     * **Elle paie un geste qu'on ne pourrait plus payer.**.
     */
    public function testItPaysForAGestureOneCouldNoLongerAfford(): void
    {
        $player = $this->getPlayer();
        $player->setLife(20);
        $player->setEnergy(0);
        $this->persistAndFlush($player);

        $spell = $this->conversion(lifeCost: 8, energyCost: 6);

        self::assertTrue(
            $this->resolver->consumeEnergy($player, $spell),
            'Le geste est refuse : la conversion se resout apres le controle de cout, donc trop tard.'
        );

        // 8 PV a x1 (12 s / 6 s, penalite de moitie) rendent 8 PM, dont 6 sont
        // aussitot depenses par le geste lui-meme.
        self::assertSame(12, $player->getLife());
        self::assertSame(2, $player->getEnergy());
    }

    /**
     * Un geste ordinaire ne convertit rien.
     *
     * Les 253 gestes livres portent `lifeCost = 0` : la forme est une decision
     * d'auteur, et son absence doit etre strictement sans effet.
     */
    public function testAnOrdinaryGestureConvertsNothing(): void
    {
        $player = $this->getPlayer();
        $player->setLife(20);
        $player->setEnergy(10);
        $this->persistAndFlush($player);

        $spell = $this->conversion(lifeCost: 0, energyCost: 4);

        self::assertSame(0, $this->resolver->convert($player, $spell));
        self::assertTrue($this->resolver->consumeEnergy($player, $spell));
        self::assertSame(20, $player->getLife());
        self::assertSame(6, $player->getEnergy());
    }

    /**
     * **Elle ne remplit jamais au-dela du pool**, et ne prend alors rien.
     *
     * Des PM crees au-dela du maximum seraient une ressource nee de rien ; et
     * retirer la vie sans rendre les PM ferait payer le joueur pour un echange
     * qui n'a pas eu lieu. Les deux se refusent ensemble.
     */
    public function testItNeitherOverfillsNorChargesForNothing(): void
    {
        $player = $this->getPlayer();
        $player->setLife(20);
        $player->setEnergy($player->getMaxEnergy());
        $this->persistAndFlush($player);

        $spell = $this->conversion(lifeCost: 8, energyCost: 0);

        self::assertSame(0, $this->resolver->convert($player, $spell));
        self::assertSame(20, $player->getLife(), 'La vie est prise pour un echange qui n\'a pas eu lieu.');
    }

    /**
     * **Elle ne tue jamais** — mais elle peut laisser a un coup de la mort.
     */
    public function testItNeverKills(): void
    {
        $player = $this->getPlayer();
        $player->setLife(4);
        $player->setEnergy(0);
        $this->persistAndFlush($player);

        $this->resolver->convert($player, $this->conversion(lifeCost: 20, energyCost: 0));

        self::assertSame(1, $player->getLife());
        self::assertFalse($player->isDead());
    }

    private function conversion(int $lifeCost, int $energyCost): Spell
    {
        $spell = new Spell();
        $spell->setSlug('blood-price-test');
        $spell->setName('Prix du sang');
        $spell->setDescription('Un geste de conversion, ecrit pour ce test.');
        $spell->setLifeCost($lifeCost);
        $spell->setEnergyCost($energyCost);
        $spell->setLevel(1);
        $spell->setCreatedAt(new \DateTime());
        $spell->setUpdatedAt(new \DateTime());

        $this->persistAndFlush($spell);

        return $spell;
    }
}
