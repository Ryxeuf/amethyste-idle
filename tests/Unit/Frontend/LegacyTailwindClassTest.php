<?php

namespace App\Tests\Unit\Frontend;

use App\Frontend\LegacyTailwindScanner;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou de la migration Tailwind 4.
 *
 * Le projet compilait deja en v4, mais chargeait en plus le CDN Tailwind 2.2.19.
 * Les declarations hors calque l'emportant sur celles d'un `@layer`, c'est la v2
 * qui servait tous les noms communs : l'interface tournait en v2 sans que
 * personne l'ait decide, et la bascule de palette du systeme de design n'aurait
 * rien bascule.
 *
 * Le CDN retire, deux defauts sont apparus, tous deux invisibles a la relecture :
 * les classes supprimees en v4 ne peignent plus rien, et celles dont le nom
 * survit avec un autre sens changent l'ecran en silence.
 *
 * C'est la meme lecon que le reste du projet : un controle qu'on doit penser a
 * declencher n'est pas un controle.
 */
class LegacyTailwindClassTest extends TestCase
{
    private function scanner(): LegacyTailwindScanner
    {
        return new LegacyTailwindScanner(\dirname(__DIR__, 3));
    }

    /**
     * Aucun gabarit ni controleur ne cite une classe d'avant la v4.
     */
    public function testNoLegacyTailwindClassRemains(): void
    {
        $offenders = $this->scanner()->scan();

        $message = '';
        foreach ($offenders as $file => $hits) {
            $message .= sprintf("\n  %s : %s", $file, implode(', ', $hits));
        }
        foreach (LegacyTailwindScanner::REPLACEMENTS as $legacy => $advice) {
            if (str_contains($message, $legacy)) {
                $message .= sprintf("\n  → %s se remplace par %s", $legacy, $advice);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Ces classes datent d\'avant Tailwind 4 : la v4 les ignore, ou leur donne un autre sens.' . $message,
        );
    }

    /**
     * Le scan trouve bien quelque chose quand il y a quelque chose a trouver.
     *
     * Sans cette borne, une expression rationnelle cassee rendrait le test
     * precedent vert sur un ensemble vide.
     */
    public function testScannerDetectsALegacyClass(): void
    {
        $fixture = sys_get_temp_dir() . '/tw-legacy-' . uniqid();
        mkdir($fixture . '/templates', 0o777, true);
        file_put_contents(
            $fixture . '/templates/probe.html.twig',
            '<div class="bg-black bg-opacity-50 flex-shrink-0 focus:outline-none">x</div>'
            . '<style>.x { flex-shrink: 0 }</style>',
        );

        $hits = (new LegacyTailwindScanner($fixture))->scan();

        $this->assertArrayHasKey('templates/probe.html.twig', $hits);
        $found = $hits['templates/probe.html.twig'];
        sort($found);
        $this->assertSame(['bg-opacity-50', 'flex-shrink-0', 'focus:outline-none'], $found);

        unlink($fixture . '/templates/probe.html.twig');
        rmdir($fixture . '/templates');
        rmdir($fixture);
    }
}
