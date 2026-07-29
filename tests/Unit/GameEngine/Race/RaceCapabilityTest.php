<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Race;

use App\Entity\App\Player;
use App\Entity\Game\Race;
use App\GameEngine\Race\RaceCapability;
use App\GameEngine\Race\RaceCapabilityResolver;
use PHPUnit\Framework\TestCase;

/**
 * ONB-07 — ce qu'un peuple apporte, et ce qu'il n'apporte jamais.
 *
 * Le contrat que ce jalon pose est negatif avant d'etre positif : **aucune
 * capacite ne modifie ce qu'on produit**. C'est ce qu'on verrouille ici, avant
 * meme de brancher les capacites sur leurs ecrans (ONB-07b).
 */
class RaceCapabilityTest extends TestCase
{
    public function testEachPeopleCarriesExactlyOneCapability(): void
    {
        $bySlug = [];
        foreach (RaceCapability::cases() as $capability) {
            $bySlug[$capability->raceSlug()][] = $capability;
        }

        foreach ($bySlug as $slug => $capabilities) {
            $this->assertCount(1, $capabilities, sprintf('Le peuple « %s » en porte plusieurs.', $slug));
        }

        $this->assertSame(
            ['dwarf', 'elf', 'human', 'orc'],
            $this->sorted(array_keys($bySlug)),
            'Les quatre peuples jouables portent une capacite, et eux seuls.',
        );
    }

    /**
     * La regle A11, prise au mot : une capacite touche ce qu'on **sait**,
     * jamais ce qu'on **produit**. Un peuple ne peut donc porter aucun nombre —
     * ce que garantit le type : `RaceCapability` n'expose que des cles de
     * traduction.
     */
    public function testACapabilityCarriesNoNumberAtAll(): void
    {
        foreach (RaceCapability::cases() as $capability) {
            $exposed = [
                $capability->value,
                $capability->raceSlug(),
                $capability->nameKey(),
                $capability->descriptionKey(),
            ];

            foreach ($exposed as $value) {
                $this->assertIsString($value);
                $this->assertDoesNotMatchRegularExpression(
                    '/\d/',
                    $value,
                    sprintf('« %s » expose un chiffre : une capacite ne se chiffre pas.', $capability->value),
                );
            }
        }
    }

    public function testTranslationKeysAreNamespacedPerCapability(): void
    {
        foreach (RaceCapability::cases() as $capability) {
            $this->assertSame('game.race.capability.' . $capability->value . '.name', $capability->nameKey());
            $this->assertSame('game.race.capability.' . $capability->value . '.description', $capability->descriptionKey());
        }
    }

    public function testResolverAnswersForAPlayer(): void
    {
        $player = new Player();
        $player->setRace((new Race())->setSlug('dwarf'));

        $resolver = new RaceCapabilityResolver();

        $this->assertSame(RaceCapability::ReadTheStone, $resolver->forPlayer($player));
        $this->assertTrue($resolver->playerHas($player, RaceCapability::ReadTheStone));
        $this->assertFalse($resolver->playerHas($player, RaceCapability::TheScent));
    }

    public function testResolverStaysQuietWithoutAPlayerOrARace(): void
    {
        $resolver = new RaceCapabilityResolver();

        $this->assertNull($resolver->forPlayer(null));
        $this->assertNull($resolver->forPlayer(new Player()));
        $this->assertNull($resolver->forRace((new Race())->setSlug('unknown-people')));
    }

    /**
     * @param list<string> $slugs
     *
     * @return list<string>
     */
    private function sorted(array $slugs): array
    {
        sort($slugs);

        return $slugs;
    }
}
