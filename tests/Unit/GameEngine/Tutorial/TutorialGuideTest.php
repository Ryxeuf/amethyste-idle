<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\Game\Quest;
use App\GameEngine\Tutorial\TutorialGuide;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\QuestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Le bandeau de tutoriel dit **ou**, et son lien mene ailleurs qu'ici.
 *
 * **Le defaut, tel qu'il se jouait.** Un joueur neuf lisait « Choisissez une
 * arme chez la maitresse d'armes » au-dessus d'un lien « Aller a la Zone »
 * pose sur l'ecran de zone — donc un lien qui rechargeait la page. Rien ne
 * disait dans quelle zone se tient Ysold, ni qu'il fallait d'abord accepter la
 * quete pour que lui parler compte. Trois murs a la suite, sur le premier pas
 * du jeu.
 */
class TutorialGuideTest extends TestCase
{
    private EntityRepository&MockObject $pnjs;
    private TutorialGuide $guide;

    /** @var list<Quest> */
    private array $arcQuests = [];

    private int $arcDone = 0;

    protected function setUp(): void
    {
        $quests = $this->createMock(QuestRepository::class);
        $completed = $this->createMock(PlayerQuestCompletedRepository::class);
        $this->pnjs = $this->createMock(EntityRepository::class);

        // Les stubs lisent des champs plutot que de figer une valeur : un test
        // qui rejoue plusieurs arcs a la suite verrait sinon le **premier**
        // gagner, PHPUnit conservant le matcher pose en premier.
        $quests->method('findByStoryArc')->willReturnCallback(fn (): array => $this->arcQuests);
        $completed->method('countCompletedInArc')->willReturnCallback(fn (): int => $this->arcDone);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($this->pnjs);

        $this->guide = new TutorialGuide($quests, $completed, $entityManager);
    }

    /**
     * **Le premier obstacle du jeu, et il n'etait dit nulle part.**.
     *
     * Aucune quete de l'acte I n'est acceptee d'office a la creation du
     * personnage. Or un objectif ne progresse que sur une quete acceptee :
     * parler a Ysold avant de l'avoir acceptee ne compte pas, et le tutoriel
     * parait gele pendant qu'on fait exactement ce qu'il demande.
     */
    public function testAnUnacceptedQuestSendsToTheJournalFirst(): void
    {
        $quest = $this->quest(['talk_to' => [['pnj_id' => 7, 'name' => 'Ysold']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor(new Player());

        self::assertNotNull($destination);
        self::assertSame('app_game_quests', $destination->route, 'Une quete non acceptee doit d\'abord envoyer au journal.');
        self::assertSame('accept', $destination->actionKey);
    }

    /**
     * Le lieu et la personne viennent de la donnee, jamais d'une chaine ecrite
     * a cote : c'est la zone du PNJ que la quete designe.
     */
    public function testItNamesThePlaceAndThePerson(): void
    {
        $fanal = $this->zone('Le Fanal');
        $ysold = $this->pnj(7, 'Ysold, maitresse d\'armes', $fanal);
        $this->pnjs->method('find')->willReturn($ysold);

        $quest = $this->quest(['talk_to' => [['pnj_id' => 7, 'name' => 'Ysold, maitresse d\'armes']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn($fanal, $quest));

        self::assertNotNull($destination);
        self::assertSame('Le Fanal', $destination->place);
        self::assertSame('Ysold, maitresse d\'armes', $destination->person);
        self::assertSame('Le Fanal — Ysold, maitresse d\'armes', $destination->where());
    }

    /**
     * **Le lien repare.** Quand le PNJ est joignable, la destination est son
     * dialogue — pas l'ecran d'ou on lit le bandeau.
     */
    public function testAReachablePnjIsLinkedDirectly(): void
    {
        $fanal = $this->zone('Le Fanal');
        $ysold = $this->pnj(7, 'Ysold', $fanal);
        $this->pnjs->method('find')->willReturn($ysold);

        $quest = $this->quest(['talk_to' => [['pnj_id' => 7, 'name' => 'Ysold']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn($fanal, $quest));

        self::assertNotNull($destination);
        self::assertSame('app_game_pnj_talk', $destination->route);
        self::assertSame(['id' => 7], $destination->routeParams);
        self::assertNotSame('app_game_zone', $destination->route, 'Le lien ne doit plus pointer sur l\'ecran de zone.');
    }

    /**
     * Un PNJ n'est joignable que depuis sa zone (ZON-27a) : envoyer sur son
     * dialogue depuis ailleurs donnerait un 404. On envoie donc a la carte, et
     * le libelle nomme la zone a rejoindre.
     */
    public function testAnUnreachablePnjSendsToTheWorldMap(): void
    {
        $fanal = $this->zone('Le Fanal');
        $ysold = $this->pnj(7, 'Ysold', $fanal);
        $this->pnjs->method('find')->willReturn($ysold);

        $quest = $this->quest(['talk_to' => [['pnj_id' => 7, 'name' => 'Ysold']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn($this->zone('La Foret'), $quest));

        self::assertNotNull($destination);
        self::assertSame('app_game_world_map', $destination->route);
        self::assertSame('travel_to', $destination->actionKey);
        self::assertSame(['%place%' => 'Le Fanal'], $destination->actionParams);
    }

    /**
     * `pnj_id` vaut 0 dans les fixtures de quete, recale apres flush par
     * `QuestChainFixtures`. Une base ou le recalage n'a pas tourne ne doit pas
     * perdre le guide : le nom porte par la quete suffit a retrouver le PNJ.
     */
    public function testItFallsBackOnThePnjNameWhenTheIdWasNeverRealigned(): void
    {
        $fanal = $this->zone('Le Fanal');
        $ysold = $this->pnj(7, 'Ysold', $fanal);
        $this->pnjs->expects(self::never())->method('find');
        $this->pnjs->method('findOneBy')->with(['name' => 'Ysold'])->willReturn($ysold);

        $quest = $this->quest(['talk_to' => [['pnj_id' => 0, 'name' => 'Ysold']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn($fanal, $quest));

        self::assertNotNull($destination);
        self::assertSame('app_game_pnj_talk', $destination->route);
    }

    /**
     * Chaque geste de l'acte I a son ecran, et c'est l'ecran ou le geste se
     * fait : l'equipement pour porter et pour sertir, l'atelier pour fabriquer.
     */
    public function testEachGestureHasItsOwnScreen(): void
    {
        foreach ([
            'equip_item' => 'app_game_inventory_equipment_list',
            'socket_materia' => 'app_game_inventory_equipment_list',
            'gather' => 'app_game_zone',
            'craft_item' => 'app_game_craft',
            'travel' => 'app_game_world_map',
            'start_expedition' => 'app_game_zone',
        ] as $gesture => $expected) {
            $quest = $this->quest(['gesture' => [['gesture' => $gesture]]]);
            $this->arc([$quest], done: 0);

            $destination = $this->guide->destinationFor($this->playerIn(null, $quest));

            self::assertNotNull($destination, sprintf('Le geste « %s » n\'a pas de destination.', $gesture));
            self::assertSame($expected, $destination->route, sprintf('Le geste « %s » n\'envoie pas au bon ecran.', $gesture));
        }
    }

    /**
     * Le mannequin n'appartient a aucune zone : il n'y a pas de lieu a nommer,
     * seulement l'ecran d'ou on le dresse.
     */
    public function testTheDummyStepPointsAtTheZoneScreen(): void
    {
        $quest = $this->quest([
            'monsters' => [['slug' => 'training_dummy_still', 'count' => 1]],
            'gesture' => [['gesture' => 'cast_spell']],
        ]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn(null, $quest));

        self::assertNotNull($destination);
        self::assertSame('app_game_zone', $destination->route);
        self::assertSame('dummy', $destination->actionKey, 'Le mannequin passe avant le geste : c\'est lui qu\'on va chercher.');
        self::assertNull($destination->where(), 'Un mannequin n\'a pas de lieu.');
    }

    /**
     * L'arc est la source : la prochaine quete est celle dont le rang suit le
     * nombre de quetes terminees — la meme deduction que `TutorialStep`.
     */
    public function testTheCurrentQuestFollowsTheArc(): void
    {
        $first = $this->quest([]);
        $second = $this->quest([]);
        $this->arc([$first, $second], done: 1);

        self::assertSame($second, $this->guide->currentQuest(new Player()));
    }

    /**
     * L'arc termine ne guide plus vers rien.
     */
    public function testAFinishedArcHasNoDestination(): void
    {
        $this->arc([$this->quest([])], done: 1);

        self::assertNull($this->guide->currentQuest(new Player()));
        self::assertNull($this->guide->destinationFor(new Player()));
    }

    /**
     * Une exigence d'une forme inattendue ne doit pas priver le bandeau de son
     * lien : ce serait rejouer le defaut qu'on corrige.
     */
    public function testAnUnknownRequirementStillLeadsSomewhere(): void
    {
        $quest = $this->quest(['gesture' => [['gesture' => 'apprivoiser_un_dragon']]]);
        $this->arc([$quest], done: 0);

        $destination = $this->guide->destinationFor($this->playerIn(null, $quest));

        self::assertNotNull($destination, 'Un geste inconnu doit retomber sur la route de l\'etape, pas supprimer le lien.');
        self::assertSame('step_weapon', $destination->actionKey);
    }

    /**
     * @param list<Quest> $quests
     */
    private function arc(array $quests, int $done): void
    {
        $this->arcQuests = $quests;
        $this->arcDone = $done;
    }

    /**
     * @param array<string, mixed> $requirements
     */
    private function quest(array $requirements): Quest
    {
        $quest = new Quest();
        $quest->setName('L\'Eveil');
        $quest->setDescription('...');
        $quest->setRequirements($requirements);
        $this->setId($quest, random_int(1000, 99999));

        return $quest;
    }

    private function zone(string $name): Zone
    {
        $zone = new Zone();
        $zone->setSlug(strtolower(str_replace(' ', '-', $name)));
        $zone->setName($name);
        $this->setId($zone, crc32($name));

        return $zone;
    }

    private function pnj(int $id, string $name, ?Zone $zone): Pnj
    {
        $pnj = new Pnj();
        $pnj->setName($name);
        $pnj->setZone($zone);
        $this->setId($pnj, $id);

        return $pnj;
    }

    /**
     * Un joueur dans une zone, avec la quete deja acceptee.
     */
    private function playerIn(?Zone $zone, Quest $quest): Player
    {
        $player = new Player();
        $player->setCurrentZone($zone);

        $playerQuest = new \App\Entity\App\PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $player->getQuests()->add($playerQuest);

        return $player;
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
