<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PlayerNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ONB-06 — deux noms sont « le meme nom » quand ils se lisent pareil.
 *
 * `player.name` portait une contrainte d'unicite, mais PostgreSQL compare des
 * octets : se faire passer pour quelqu'un ne demandait aucun effort.
 */
class PlayerNameNormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function equivalentPairs(): iterable
    {
        yield 'la casse' => ['Claire', 'claire'];
        yield 'les espaces' => ['Le Fanal', 'LeFanal'];
        yield 'les traits d\'union' => ['Jean-Pierre', 'JeanPierre'];
        yield 'la ponctuation' => ['Cl.aire', 'Claire'];
        yield 'les accents' => ['Thérèse', 'Therese'];
        yield 'la ligature' => ['Cœur', 'Coeur'];
        // Le « е » et le « а » sont cyrilliques : a l'ecran, rien ne distingue
        // ces deux chaines.
        yield 'un homoglyphe cyrillique' => ['Clairе', 'Claire'];
        yield 'un homoglyphe grec' => ['Αldric', 'Aldric'];
        yield 'un chiffre qui imite une lettre' => ['Cla0re', 'Claore'];
    }

    #[DataProvider('equivalentPairs')]
    public function testNamesThatReadTheSameNormalizeTheSame(string $left, string $right): void
    {
        $normalizer = new PlayerNameNormalizer();

        $this->assertSame($normalizer->normalize($left), $normalizer->normalize($right));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function distinctPairs(): iterable
    {
        yield 'deux noms differents' => ['Aldric', 'Elara'];
        yield 'une lettre en plus' => ['Claire', 'Clairee'];
        yield 'un prefixe' => ['Aldric', 'Aldrica'];
    }

    #[DataProvider('distinctPairs')]
    public function testDistinctNamesStayDistinct(string $left, string $right): void
    {
        $normalizer = new PlayerNameNormalizer();

        $this->assertNotSame($normalizer->normalize($left), $normalizer->normalize($right));
    }

    /**
     * La normalisation produit une forme de **comparaison**. Le joueur garde ce
     * qu'il a tape — c'est `Player::name` qui s'affiche.
     */
    public function testNormalizationNeverBecomesTheDisplayedName(): void
    {
        $this->assertSame('therese', (new PlayerNameNormalizer())->normalize('Thérèse'));
    }

    public function testAnEmptyOrPunctuationOnlyNameNormalizesToNothing(): void
    {
        $normalizer = new PlayerNameNormalizer();

        $this->assertSame('', $normalizer->normalize('   '));
        $this->assertSame('', $normalizer->normalize('---'));
    }
}
