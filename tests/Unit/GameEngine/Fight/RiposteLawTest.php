<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Enum\GestureForm;
use App\GameEngine\Fight\RiposteLaw;
use PHPUnit\Framework\TestCase;

/**
 * La loi de la riposte, et son garde-fou (ARC-18a).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 4. Ce fichier tient la seule chose qui fasse
 * de la riposte un archetype plutot qu'un bonus : *elle ne se declenche jamais
 * sur des degats evites*.
 */
class RiposteLawTest extends TestCase
{
    /**
     * **Le garde-fou d'admission de la forme.**.
     *
     * Si l'esquive ou l'absorption declenchaient la riposte, **l'encaisse
     * optimale consisterait a se faire toucher expres** — et un archetype dont
     * le jeu optimal est de baisser sa garde n'est pas un archetype d'encaisse.
     *
     * La lecture est arithmetique et non declarative : on ne demande pas *« la
     * cible a-t-elle esquive ? »* mais *« combien de points de vie ont ete
     * retires ? »*. Ca ferme d'un coup tous les chemins d'evitement — esquive,
     * bouclier, garde — **y compris ceux qui n'existent pas encore**.
     */
    public function testARiposteNeverAnswersAnAvoidedBlow(): void
    {
        self::assertFalse(RiposteLaw::triggersOn(0), 'Un coup qui ne retire rien ne se riposte pas.');
        self::assertSame(0, RiposteLaw::returnedDamage(0, 5));
        self::assertSame(0, RiposteLaw::returnedDamage(-3, 5), 'Un soin n\'est pas un coup.');
    }

    /**
     * Un coup qui a mordu appelle la riposte, pour ce que l'application porte.
     */
    public function testABlowThatLandsIsAnswered(): void
    {
        self::assertTrue(RiposteLaw::triggersOn(1));
        self::assertSame(5, RiposteLaw::returnedDamage(12, 5));
    }

    /**
     * **La riposte rend une valeur fixe, jamais une part des degats recus.**.
     *
     * Une part grandirait avec le palier de l'adversaire : la riposte vaudrait
     * le plus contre ceux qui frappent le plus fort, c'est-a-dire qu'elle
     * recompenserait d'etre en danger — ce que le garde-fou ci-dessus interdit
     * deja dans l'autre sens.
     */
    public function testWhatIsReturnedDoesNotFollowWhatWasTaken(): void
    {
        self::assertSame(
            RiposteLaw::returnedDamage(3, 4),
            RiposteLaw::returnedDamage(300, 4),
            'La riposte suit ce que le porteur a pose, jamais ce que l\'adversaire frappe.',
        );
    }

    /**
     * Une application sans valeur ne riposte pas — ce n'est pas une riposte.
     */
    public function testAnApplicationWithoutValueReturnsNothing(): void
    {
        self::assertSame(0, RiposteLaw::returnedDamage(10, null));
        self::assertSame(0, RiposteLaw::returnedDamage(10, -2));
    }

    /**
     * **La borne des depots offensifs** (§ 13.3, correction 21).
     *
     * *Un depot offensif ne depasse jamais un tour d'attaque par tour investi.*
     * La riposte n'en investit qu'un — celui ou on la pose —, donc sa valeur
     * totale sur sa duree tient dans un tour d'attaque.
     */
    public function testARiposteStaysWithinTheOffensiveDepositBound(): void
    {
        // 3 par coup sur 4 tours vaut 12 : au-dela d'un tour d'attaque a 10.
        self::assertFalse(RiposteLaw::isWithinOffensiveDepositBound(3, 4, 10));
        self::assertTrue(RiposteLaw::isWithinOffensiveDepositBound(2, 4, 10));
        self::assertTrue(RiposteLaw::isWithinOffensiveDepositBound(10, 1, 10));
    }

    /**
     * **Le vocabulaire des formes est ferme, et une seule est livree.**.
     *
     * Les sept autres sont nommees sans lecteur, et c'est delibere : *une forme
     * inerte n'est pas fausse, mais elle serait un mensonge d'interface si on la
     * laissait s'ecrire sans le savoir* (la discipline d'ARC-16a).
     */
    public function testOnlyOneFormIsImplementedAndTheVocabularyIsClosed(): void
    {
        self::assertCount(8, GestureForm::cases(), 'Huit formes, et une neuvieme est une decision de moteur.');
        self::assertSame([GestureForm::Riposte], GestureForm::implemented());
        self::assertTrue(GestureForm::Riposte->isImplemented());
        self::assertFalse(GestureForm::Familiar->isImplemented());
    }
}
