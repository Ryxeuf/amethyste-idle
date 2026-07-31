<?php

namespace App\Tests\Unit\Onboarding;

use App\GameEngine\Onboarding\OnboardingFunnel;
use PHPUnit\Framework\TestCase;

/**
 * Les sept indicateurs du tunnel (ONB-19b).
 *
 * GAME_ONBOARDING § 9 : *sans mesure, on repare a l'aveugle*. ONB-19a a ferme
 * ce qui ne doit jamais arriver ; ces indicateurs disent ou les gens s'arretent,
 * ce qu'aucun test ne peut dire.
 *
 * La regle que ce fichier protege tient en une phrase : **un taux sur zero
 * observation n'existe pas**. C'est la seule facon qu'un ecran de pilotage ne
 * mente pas le jour de son ouverture, quand tous les compteurs sont a zero et
 * qu'un `0 %` se lit comme un echec au lieu d'une absence.
 */
class OnboardingFunnelTest extends TestCase
{
    /**
     * Zero observation ne donne pas zero pour cent, mais rien du tout.
     *
     * Le premier jour, aucun compte n'existe. Un tableau qui annonce « 0 % de
     * comptes aboutis » fait paniquer pour une division qui n'a pas eu lieu.
     */
    public function testARateOverNoObservationDoesNotExist(): void
    {
        $empty = new OnboardingFunnel();

        self::assertNull($empty->characterShare());
        self::assertNull($empty->actOneShare());
        self::assertNull($empty->verifiedShare());
        self::assertNull($empty->stillActiveShare(1));
    }

    /**
     * Zero observe, en revanche, se dit bien zero.
     *
     * C'est l'autre moitie de la regle : cent comptes dont aucun n'a abouti est
     * une information, et la confondre avec l'absence de comptes la perdrait.
     */
    public function testAnObservedZeroIsStillZero(): void
    {
        $funnel = new OnboardingFunnel(accounts: 100, accountsWithCharacter: 0);

        self::assertSame(0, $funnel->characterShare());
    }

    /**
     * L'abandon dans le tunnel se **derive**, il ne se compte pas.
     *
     * Un compteur d'abandons serait a incrementer a chaque pas du tunnel, et se
     * tromperait au premier pas ajoute. La soustraction, elle, reste juste quel
     * que soit le nombre de pas.
     */
    public function testTunnelAbandonIsDerivedFromWhatExists(): void
    {
        $funnel = new OnboardingFunnel(accounts: 140, accountsWithCharacter: 96);

        self::assertSame(44, $funnel->abandonedInTunnel());
        self::assertSame(69, $funnel->characterShare());
    }

    /**
     * Un compte de plus que de personnages ne rend jamais un abandon negatif.
     *
     * Le cas parait absurde jusqu'au jour ou un personnage survit a la
     * suppression de son compte : l'ecran doit alors afficher un chiffre faux
     * mais lisible, pas un nombre negatif qui arrete la lecture.
     */
    public function testAbandonNeverGoesNegative(): void
    {
        $funnel = new OnboardingFunnel(accounts: 3, accountsWithCharacter: 10);

        self::assertSame(0, $funnel->abandonedInTunnel());
    }

    /**
     * Les deux fenetres de retour sont celles du plan, et rien d'autre.
     *
     * Une troisieme fenetre ajoutee « pour voir » rendrait l'ecran illisible et
     * la mesure incomparable d'une semaine sur l'autre.
     */
    public function testTheReturnWindowsAreTheOnesThePlanNames(): void
    {
        self::assertSame([1, 7], OnboardingFunnel::RETURN_DAYS);
    }

    /**
     * L'avertissement de l'ecran s'efface tout seul le jour ou il devient faux.
     *
     * L'indicateur « e-mail verifie a J+7 » restera a 0 % tant qu'ONB-02/04 ne
     * sont pas livres : **rien n'ecrit `emailVerifiedAt`**. L'ecran le dit, et
     * ce test tient l'accord entre les deux dans les deux sens — le jour ou un
     * chemin de code renseignera la colonne, il tombera, et l'avertissement
     * devra partir. Une note qui survit a sa raison d'etre est un mensonge de
     * plus.
     */
    public function testTheUnwiredVerificationNoticeExpiresWithItsReason(): void
    {
        $root = \dirname(__DIR__, 3);
        $screen = (string) file_get_contents($root . '/templates/admin/onboarding/index.html.twig');

        self::assertNotSame('', $screen, 'L\'ecran est illisible : le test ne verifie rien.');

        $writers = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/src'));
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');
            // L'entite le porte, le lecteur d'indicateurs le lit : ni l'un ni
            // l'autre ne branche la verification.
            if ($relative === 'src/Entity/User.php' || str_starts_with($relative, 'src/GameEngine/Onboarding/')) {
                continue;
            }

            if (str_contains((string) file_get_contents($file->getPathname()), 'emailVerifiedAt')) {
                $writers[] = $relative;
            }
        }

        if ($writers === []) {
            self::assertStringContainsString(
                'non branché',
                $screen,
                'L\'indicateur de verification affiche un taux sans dire qu\'aucun code ne renseigne la colonne : '
                . 'un zero se lirait comme un echec au lieu d\'une absence.',
            );

            return;
        }

        self::assertStringNotContainsString(
            'non branché',
            $screen,
            'La verification d\'e-mail est branchee (' . implode(', ', $writers) . ') : l\'avertissement de '
            . 'l\'ecran est devenu faux et doit partir.',
        );
    }

    /**
     * Le taux de retour se lit sur les personnages, pas sur les comptes.
     *
     * Un compte sans personnage n'a jamais joue : l'inclure au denominateur
     * ferait passer un probleme de tunnel pour un probleme de retention, et on
     * reparerait le mauvais bout.
     */
    public function testTheReturnRateIsReadAgainstCharacters(): void
    {
        $funnel = new OnboardingFunnel(
            accounts: 200,
            characters: 50,
            stillActive: [1 => 25, 7 => 10],
        );

        self::assertSame(50, $funnel->stillActiveShare(1));
        self::assertSame(20, $funnel->stillActiveShare(7));
    }
}
