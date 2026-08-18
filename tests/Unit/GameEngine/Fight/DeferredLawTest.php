<?php

namespace App\Tests\Unit\GameEngine\Fight;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Enum\GestureForm;
use App\GameEngine\Fight\DeferredLaw;
use App\GameEngine\Fight\DeferredQueue;
use PHPUnit\Framework\TestCase;

/**
 * Le geste qui frappe plus tard (ARC-18f).
 *
 * GAME_ARCHETYPES § 13.1, forme n° 8 — **la seule des huit qui exploite
 * l'asynchronie au lieu de la subir** : dans un donjon ou un tour peut durer
 * des heures, un geste qui se resout deux tours plus tard se resout pendant le
 * tour de quelqu'un d'autre, si bien que le joueur qui l'a pose continue d'agir
 * apres etre parti.
 */
class DeferredLawTest extends TestCase
{
    /**
     * **Des tours, jamais des secondes.**.
     *
     * Le garde-fou du canon, et le meme compteur que les depots (§ 7 bis) :
     * une echeance en temps reel ferait exploser la bombe avant que le tour
     * suivant n'ait ete joue, ou trois tours trop tard selon la vitesse de
     * connexion des autres — *le geste dependrait de la ponctualite d'inconnus
     * plutot que du combat*.
     */
    public function testItResolvesInEncounterTurns(): void
    {
        self::assertSame(5, DeferredLaw::resolvesAt(3, 2));
        self::assertSame(4, DeferredLaw::resolvesAt(3, 1));

        self::assertFalse(DeferredLaw::isDue(5, 4));
        self::assertTrue(DeferredLaw::isDue(5, 5));

        // La comparaison est large : un tour peut etre saute — un joueur qui
        // fuit, une rencontre qui change d'etape —, et une egalite stricte
        // laisserait la bombe dans la file **pour toujours**, ni resolue ni
        // effacee.
        self::assertTrue(DeferredLaw::isDue(5, 9));
    }

    /**
     * Les deux bornes du delai.
     *
     * En bas : un differe a zero tour se resout dans le tour ou il est joue —
     * *ce n'est pas un differe, c'est un geste ordinaire ecrit de facon
     * compliquee*. En haut : sans borne, un differe pose au tour 1 pour le tour
     * 30 serait **oublie de tout le monde**, et un geste qu'on ne relie pas a
     * sa cause n'est pas une mecanique, c'est du bruit.
     */
    public function testTheDelayIsBoundedAtBothEnds(): void
    {
        self::assertSame(1, DeferredLaw::MIN_DELAY);
        self::assertSame(3, DeferredLaw::MAX_DELAY);

        self::assertSame(1, DeferredLaw::delayFor(0));
        self::assertSame(1, DeferredLaw::delayFor(-4));
        self::assertSame(2, DeferredLaw::delayFor(2));
        self::assertSame(3, DeferredLaw::delayFor(30));
    }

    /**
     * **Attendre ne rapporte rien.**.
     *
     * La correction 5 transposee — *la duree etale la valeur, elle ne
     * l'augmente pas*. Si differer plus longtemps rapportait plus, poser sa
     * bombe au tour le plus lointain serait toujours correct, et le differe
     * cesserait d'etre un choix pour devenir un calcul. Ce qui fait la valeur
     * de la forme n'est pas d'attendre, c'est **d'agir quand on n'est pas la**.
     */
    public function testWaitingLongerNeverPaysMore(): void
    {
        foreach ([1, 2, 3] as $delay) {
            self::assertSame(40, DeferredLaw::payload(40, $delay), sprintf('Delai %d', $delay));
        }
    }

    /**
     * **Lire la file, c'est la consommer.**.
     *
     * Les separer laisserait un appelant lire, agir, et oublier de vider —
     * c'est-a-dire une bombe qui explose a chaque tour jusqu'a la fin du
     * combat. La seule facon de ne pas ecrire ce defaut est de rendre
     * impossible de lire sans consommer.
     */
    public function testCollectingIsConsuming(): void
    {
        $queue = new DeferredQueue();
        $fight = new Fight();
        $fight->setStep(3);
        $player = $this->player(1);

        $queue->defer($fight, $player, 40, 2);

        self::assertSame([], $queue->collectDue($fight), 'Une bombe posee explose le tour meme.');
        self::assertCount(1, $queue->all($fight));

        $fight->setStep(5);
        $due = $queue->collectDue($fight);

        self::assertCount(1, $due);
        self::assertSame(40, $due[0]['value']);
        self::assertSame([], $queue->all($fight), 'La bombe est restee dans la file : elle explosera a chaque tour.');
        self::assertSame([], $queue->collectDue($fight));
    }

    /**
     * **Elle meurt avec la rencontre.**.
     *
     * Le rangement le garantit — la file vit dans les metadonnees du combat —,
     * et sans cela un differe pose puis fui exploserait dans la rencontre
     * suivante, c'est-a-dire sur un monstre qui n'existait pas quand on a vise.
     */
    public function testItDiesWithTheEncounter(): void
    {
        $queue = new DeferredQueue();
        $fight = new Fight();
        $fight->setStep(1);

        $queue->defer($fight, $this->player(1), 40, 2);
        self::assertCount(1, $queue->all($fight));

        $next = new Fight();
        $next->setStep(9);
        self::assertSame([], $queue->all($next));
        self::assertSame([], $queue->collectDue($next));
    }

    public function testTheFormIsDeclaredReadable(): void
    {
        self::assertTrue(GestureForm::Delayed->isImplemented());
    }

    private function player(int $id): Player
    {
        $player = new Player();
        (new \ReflectionProperty(Player::class, 'id'))->setValue($player, $id);

        return $player;
    }
}
