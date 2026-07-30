<?php

namespace App\Tests\Unit\GameEngine\Quest;

use App\Entity\App\PlayerQuest;
use App\Enum\QuestGesture;
use App\GameEngine\Quest\DailyQuestService;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\GameEngine\Quest\PlayerQuestUpdater;
use App\GameEngine\Quest\QuestTrackingFormater;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat des gestes de quete (ONB-12a).
 *
 * Un objectif de quete a une propriete desagreable : quand il est mal cable,
 * **rien ne se plaint**. La quete reste ouverte, le joueur refait le geste, et
 * la seule facon de s'en apercevoir est de jouer la chaine jusqu'au bout. Dans
 * une chaine d'introduction, cela se traduit par un abandon.
 *
 * Les lois ci-dessous ferment les trois facons connues de rater ce cablage :
 * un geste que personne n'emet, un type de suivi que le calcul de progression
 * ignore, et une cible qui ne correspond a rien.
 */
class QuestGestureContractTest extends TestCase
{
    /**
     * Tout geste declare est reellement emis quelque part.
     *
     * L'enumeration ne prouve rien a elle seule : elle empeche d'ecrire un nom
     * fantaisiste dans une fixture, pas d'ajouter un cas qu'aucun appelant ne
     * declenche. C'est la moitie du contrat qui manque le plus souvent.
     */
    public function testEveryGestureIsAnnouncedBySomeone(): void
    {
        $sources = $this->sourceTree();

        $orphans = [];
        foreach (QuestGesture::cases() as $gesture) {
            if (!str_contains($sources, 'QuestGesture::' . $gesture->name)) {
                $orphans[] = $gesture->value;
            }
        }

        self::assertSame([], $orphans, sprintf(
            "Ces gestes ne sont emis par aucun appelant : %s.\nUne quete qui les demande ne se terminerait jamais, et rien ne le signalerait.",
            implode(', ', $orphans),
        ));
    }

    /**
     * Tout type de suivi produit est compte dans la progression.
     *
     * Le piege est plus vicieux qu'un simple oubli : `getPlayerQuestProgress`
     * renvoie **100** quand le total necessaire vaut zero. Une quete dont le
     * seul objectif serait d'un type absent de la liste ne resterait donc pas
     * bloquee — elle serait terminee des l'acceptation.
     */
    public function testEveryTrackedTypeIsCountedInProgress(): void
    {
        $formater = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Quest/QuestTrackingFormater.php');
        $helper = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Quest/PlayerQuestHelper.php');

        preg_match_all("/\\\$tracking\['([a-z_]+)'\] = /", $formater, $matches);
        self::assertNotEmpty($matches[1], 'Aucun type de suivi trouve : la loi ne verifierait rien.');

        $uncounted = [];
        foreach (array_unique($matches[1]) as $type) {
            if (!str_contains($helper, "'" . $type . "'")) {
                $uncounted[] = $type;
            }
        }

        self::assertSame([], $uncounted, sprintf(
            "Ces types de suivi ne sont pas comptes dans la progression : %s.\nUne quete qui n'aurait que celui-la serait « terminee » des son acceptation.",
            implode(', ', $uncounted),
        ));
    }

    /**
     * Un geste inconnu est refuse a la construction du suivi.
     *
     * Le refus est pose la, et non a l'usage : c'est le seul endroit ou la
     * faute est encore rattachable a la fixture qui l'a ecrite.
     */
    public function testAnUnknownGestureIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new QuestTrackingFormater())->formatGesture([
            'gesture' => [['gesture' => 'apprendre_a_voler']],
        ]);
    }

    /**
     * Les valeurs par defaut d'un objectif de geste.
     */
    public function testAGestureNeedsNothingButItsName(): void
    {
        $entries = (new QuestTrackingFormater())->formatGesture([
            'gesture' => [['gesture' => 'cast_spell']],
        ]);

        self::assertCount(1, $entries);
        self::assertSame(0, $entries[0]['count']);
        self::assertSame(1, $entries[0]['necessary']);
        self::assertNull($entries[0]['target'], 'Sans cible declaree, n\'importe laquelle doit convenir.');
        self::assertSame('game.quest.gesture.cast_spell', $entries[0]['name']);
    }

    /**
     * Sans cible, n'importe quelle cible convient.
     */
    public function testAnUntargetedGestureAcceptsAnything(): void
    {
        $quest = $this->questTracking([['count' => 0, 'necessary' => 1, 'gesture' => 'socket_materia', 'target' => null, 'name' => 'x']]);

        $this->updaterFor($quest)->updateGesture(QuestGesture::SocketMateria, ['m1-fire', 'fire']);

        self::assertSame(1, $quest->getTracking()['gesture'][0]['count']);
    }

    /**
     * Une cible declaree est comparee a **toutes** les lectures annoncees.
     *
     * C'est ce qui permet a l'acte I de demander « une materia de votre
     * element » sans nommer d'objet : la recompense de l'etape 3 depend du
     * domaine choisi a l'etape 1, et la quete ne peut donc pas connaitre son
     * slug a l'avance.
     */
    public function testADeclaredTargetMatchesAnyAnnouncedReading(): void
    {
        $quest = $this->questTracking([['count' => 0, 'necessary' => 1, 'gesture' => 'socket_materia', 'target' => 'fire', 'name' => 'x']]);

        $this->updaterFor($quest)->updateGesture(QuestGesture::SocketMateria, ['m1-fire', 'fire']);

        self::assertSame(1, $quest->getTracking()['gesture'][0]['count']);
    }

    /**
     * Une cible qui ne correspond pas ne fait rien avancer.
     */
    public function testAMismatchedTargetAdvancesNothing(): void
    {
        $quest = $this->questTracking([['count' => 0, 'necessary' => 1, 'gesture' => 'socket_materia', 'target' => 'fire', 'name' => 'x']]);

        $this->updaterFor($quest)->updateGesture(QuestGesture::SocketMateria, ['m1-water', 'water']);

        self::assertSame(0, $quest->getTracking()['gesture'][0]['count']);
    }

    /**
     * Un autre geste ne fait rien avancer non plus.
     */
    public function testAnotherGestureAdvancesNothing(): void
    {
        $quest = $this->questTracking([['count' => 0, 'necessary' => 1, 'gesture' => 'cast_spell', 'target' => null, 'name' => 'x']]);

        $this->updaterFor($quest)->updateGesture(QuestGesture::EquipItem, ['short-sword', 'sword']);

        self::assertSame(0, $quest->getTracking()['gesture'][0]['count']);
    }

    /**
     * Le compteur ne depasse jamais ce qui est demande.
     */
    public function testAGestureNeverCountsPastWhatIsAsked(): void
    {
        $quest = $this->questTracking([['count' => 0, 'necessary' => 1, 'gesture' => 'start_expedition', 'target' => null, 'name' => 'x']]);
        $updater = $this->updaterFor($quest);

        $updater->updateGesture(QuestGesture::StartExpedition, ['short']);
        $updater->updateGesture(QuestGesture::StartExpedition, ['short']);

        self::assertSame(1, $quest->getTracking()['gesture'][0]['count']);
    }

    /**
     * @param list<array<string, mixed>> $entries
     */
    private function questTracking(array $entries): PlayerQuest
    {
        $quest = new PlayerQuest();
        $quest->setTracking(['gesture' => $entries]);

        return $quest;
    }

    private function updaterFor(PlayerQuest $quest): PlayerQuestUpdater
    {
        $helper = $this->createMock(PlayerQuestHelper::class);
        $helper->method('getCurrentQuests')->willReturn([$quest]);
        $helper->method('isPlayerQuestCompleted')->willReturn(false);

        return new PlayerQuestUpdater(
            $helper,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PlayerHelper::class),
            $this->createMock(DailyQuestService::class),
        );
    }

    private function sourceTree(): string
    {
        $root = \dirname(__DIR__, 4) . '/src';
        $sources = '';

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $sources .= (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }
}
