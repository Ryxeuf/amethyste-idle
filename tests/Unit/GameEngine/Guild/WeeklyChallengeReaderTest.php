<?php

namespace App\Tests\Unit\GameEngine\Guild;

use App\GameEngine\Guild\WeeklyChallengeReader;
use PHPUnit\Framework\TestCase;

/**
 * Le temps restant d'un defi, tel que les deux ecrans le liront (RET-08).
 *
 * `humanizeRemaining` vivait dans le controleur de guilde, en methode privee
 * statique : elle n'etait donc testable que par un test fonctionnel qui rend
 * l'ecran entier. Extraite, elle se verifie pour ce qu'elle est — une
 * conversion de secondes en une unite et une quantite.
 *
 * Ce qu'elle ne fait pas est aussi verifie que ce qu'elle fait : elle ne
 * traduit pas. L'unite part en cle Twig, et c'est la traduction qui choisit le
 * pluriel — un `sprintf('%d jours')` en PHP aurait fige la grammaire francaise
 * dans le moteur.
 */
class WeeklyChallengeReaderTest extends TestCase
{
    /**
     * Une echeance situee a N secondes de « maintenant », N pouvant etre negatif.
     *
     * `%+d` et non `+%d` : le second produirait « +-3600 seconds » pour une
     * echeance passee. PHP l'accepte, mais personne ne devrait avoir a le
     * verifier pour lire le test.
     *
     * @return array{unit: string, count: int}
     */
    private function remaining(int $seconds): array
    {
        $now = new \DateTimeImmutable('2026-07-30 12:00:00');

        return WeeklyChallengeReader::humanizeRemaining($now->modify(sprintf('%+d seconds', $seconds)), $now);
    }

    /**
     * L'unite la plus grande gagne : on dit « 2 jours », jamais « 48 heures ».
     */
    public function testTheLargestUnitWins(): void
    {
        self::assertSame(['unit' => 'days', 'count' => 2], $this->remaining(2 * 86400));
        self::assertSame(['unit' => 'hours', 'count' => 5], $this->remaining(5 * 3600));
        self::assertSame(['unit' => 'minutes', 'count' => 30], $this->remaining(30 * 60));
    }

    /**
     * Une echeance passee se dit `ended`, et jamais un negatif.
     */
    public function testAPastDeadlineIsEnded(): void
    {
        self::assertSame(['unit' => 'ended', 'count' => 0], $this->remaining(0));
        self::assertSame(['unit' => 'ended', 'count' => 0], $this->remaining(-3600));
    }

    /**
     * La derniere minute reste **une** minute, jamais zero.
     *
     * Un defi qui s'acheve dans 20 secondes doit se lire « 1 minute » : un
     * « 0 minute » se lit comme un defi termine, alors qu'il court encore.
     */
    public function testTheLastMinuteNeverRoundsToZero(): void
    {
        self::assertSame(['unit' => 'minutes', 'count' => 1], $this->remaining(20));
        self::assertSame(['unit' => 'minutes', 'count' => 1], $this->remaining(59));
    }

    /**
     * Les bornes ne laissent pas de trou : chaque seuil bascule d'un cran.
     */
    public function testEachThresholdSwitchesExactlyOnce(): void
    {
        self::assertSame('minutes', $this->remaining(3599)['unit']);
        self::assertSame('hours', $this->remaining(3600)['unit']);
        self::assertSame('hours', $this->remaining(86399)['unit']);
        self::assertSame('days', $this->remaining(86400)['unit']);
    }
}
