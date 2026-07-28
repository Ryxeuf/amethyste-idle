<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Event\CraftEvent;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\Event\Map\ButcheringEvent;
use App\Event\Map\FishingEvent;
use App\Event\Zone\PlayerTraveledEvent;
use App\Event\Zone\ZoneGatherEvent;
use App\EventListener\SettlementSedimentListener;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : la table de depot et le listener parlent de la meme chose
 * (FOY-02).
 *
 * Le tableau de `config/game/settlements.yaml` est indexe par des chaines
 * (`mob_kill`, `harvest`, `travel`…) que le listener passe telles quelles au
 * service. Rien, dans le typage, ne relie les deux : renommer une clef du YAML
 * — ou une chaine du listener — coupe le depot **sans que rien n'echoue**.
 * L'action continue d'etre jouee, plus rien ne se depose, et la difference ne
 * se voit que le jour ou quelqu'un s'etonne qu'une zone ne monte plus.
 *
 * C'est la meme famille de defaut que ZON-38, un cran plus bas : la, un abonne
 * accroche a un evenement mort ; ici, un appel accroche a une clef morte.
 * Les deux sont muets, les deux se decouvrent des semaines plus tard.
 *
 * La correspondance est donc verifiee **dans les deux sens** : une clef du YAML
 * que personne n'appelle est du chiffrage mort, et un appel sans clef est un
 * depot qui n'arrive jamais.
 */
class SettlementSedimentWiringTest extends TestCase
{
    /**
     * Clefs presentes dans le YAML mais volontairement pas encore appelees.
     *
     * **Vide, et c'est le but** : le fichier ne declare que ce qui est branche.
     * BALANCE § 23.1 chiffre trois lignes de plus — la vente conclue a l'hotel
     * des ventes, la materia lue chez les Lecteurs, la participation a un beat
     * de maree — qui n'ont pas encore d'accroche et ne sont donc **pas** dans le
     * fichier. Les y mettre d'avance creerait precisement le chiffrage muet que
     * ce test existe pour interdire ; elles y entreront avec leur listener.
     *
     * @var array<string, string>
     */
    private const NOT_YET_WIRED = [];

    /**
     * Fichiers qui appellent le depot.
     *
     * Le listener n'est plus le seul depuis RET-02b : la livraison d'une
     * commission depose elle aussi, depuis le moteur de retention. Chaque
     * nouvel appelant doit s'inscrire ici, faute de quoi sa ligne du YAML
     * passerait pour orpheline et le garde-fou refuserait un depot pourtant
     * branche — un faux positif use un garde-fou aussi surement qu'un trou.
     *
     * @var list<string>
     */
    private const DEPOSIT_CALLERS = [
        'src/EventListener/SettlementSedimentListener.php',
        'src/GameEngine/Retention/WeeklyCommissionDelivery.php',
    ];

    /**
     * @return list<string>
     */
    private function actionsCalledByTheListener(): array
    {
        $actions = [];
        foreach (self::DEPOSIT_CALLERS as $relative) {
            $path = \dirname(__DIR__, 4) . '/' . $relative;
            self::assertFileExists($path, sprintf('Appelant de depot introuvable : %s.', $relative));

            preg_match_all("/deposit\(\s*\\\$[a-zA-Z>()\\-]+,\s*'([a-z_]+)'/", (string) file_get_contents($path), $matches);
            foreach ($matches[1] as $action) {
                $actions[$action] = true;
            }
        }

        $actions = array_keys($actions);
        sort($actions);

        return $actions;
    }

    /**
     * @return list<string>
     */
    private function actionsDeclaredInConfig(): array
    {
        $definition = (new SettlementDefinitionLoader(\dirname(__DIR__, 4)))->load();
        $actions = array_keys($definition['sediment']);
        sort($actions);

        return $actions;
    }

    public function testTheListenerReadsAtLeastOneAction(): void
    {
        // Garde-fou du garde-fou : si l'extraction par expression reguliere
        // cesse de fonctionner, les deux comparaisons ci-dessous passeraient au
        // vert en ne comparant rien.
        self::assertNotEmpty($this->actionsCalledByTheListener());
    }

    public function testEveryActionTheListenerDepositsExistsInTheTable(): void
    {
        $unknown = array_values(array_diff(
            $this->actionsCalledByTheListener(),
            $this->actionsDeclaredInConfig(),
        ));

        self::assertSame([], $unknown, sprintf(
            'Le listener depose ces actions, absentes de settlements.yaml : %s. Le depot ne se ferait jamais.',
            implode(', ', $unknown),
        ));
    }

    public function testEveryChargedActionIsActuallyDeposited(): void
    {
        $orphans = array_values(array_diff(
            $this->actionsDeclaredInConfig(),
            $this->actionsCalledByTheListener(),
            array_keys(self::NOT_YET_WIRED),
        ));

        self::assertSame([], $orphans, sprintf(
            "Ces lignes de settlements.yaml sont chiffrees mais jamais appelees : %s.\n"
            . 'Branchez-les, ou inscrivez-les dans NOT_YET_WIRED avec le jalon qui les apportera.',
            implode(', ', $orphans),
        ));
    }

    /**
     * Le listener se branche sur des evenements **deja emis** : le pilier des
     * foyers n'en cree aucun. Si l'un d'eux disparait, cette liste doit changer
     * en meme temps que le code, pas six semaines plus tard.
     */
    public function testTheListenerSubscribesToTheLiveLoops(): void
    {
        $subscribed = array_keys(SettlementSedimentListener::getSubscribedEvents());

        foreach ([
            ZoneGatherEvent::NAME,
            CraftEvent::NAME,
            FishingEvent::NAME,
            ButcheringEvent::NAME,
            MobDeadEvent::NAME,
            QuestCompletedEvent::NAME,
            PlayerTraveledEvent::NAME,
        ] as $event) {
            self::assertContains($event, $subscribed);
        }
    }
}
