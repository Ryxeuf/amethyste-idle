<?php

namespace App\Tests\Unit\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Enum\TrainingMode;
use App\GameEngine\Tutorial\TrainingDummyOffer;
use App\GameEngine\Tutorial\TutorialGuide;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * La porte des mannequins, qui n'existait pas.
 *
 * `TrainingFightLauncher` etait ecrit, teste et documente depuis ONB-11 sans
 * **aucun appelant** : ni route, ni bouton. Les etapes 3 et 5 de l'acte I
 * demandent de battre un mannequin, le Fanal est `safe: true` — donc
 * `ExploreService` y force `mob: 0` — et les mannequins n'appartiennent a
 * aucune zone. La chaine de l'acte I etait **infranchissable a sa troisieme
 * etape**, sans qu'aucun message ne le dise.
 *
 * Ce qui suit tient les deux bornes de l'offre : elle apparait quand la lecon
 * la demande, et **jamais autrement** — un bouton qui poserait un combat en
 * zone sure hors du tutoriel serait un autre defaut, pas une correction.
 */
class TrainingDummyOfferTest extends TestCase
{
    private TutorialGuide&MockObject $guide;
    private EntityRepository&MockObject $monsters;
    private TrainingDummyOffer $offer;

    protected function setUp(): void
    {
        $this->guide = $this->createMock(TutorialGuide::class);
        $this->monsters = $this->createMock(EntityRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($this->monsters);

        $this->offer = new TrainingDummyOffer($this->guide, $entityManager);
    }

    public function testItOffersTheDummyTheCurrentStepAsksFor(): void
    {
        $quest = $this->quest(['monsters' => [['slug' => 'training_dummy_still', 'count' => 1]]]);
        $dummy = $this->dummy('training_dummy_still', TrainingMode::Inert);

        $this->guide->method('currentQuest')->willReturn($quest);
        $this->guide->method('hasAccepted')->willReturn(true);
        $this->monsters->method('findOneBy')->with(['slug' => 'training_dummy_still'])->willReturn($dummy);

        self::assertSame($dummy, $this->offer->pendingFor($this->player($quest)));
    }

    /**
     * Une quete non acceptee ne compte pas ses objectifs : offrir le combat
     * avant l'acceptation donnerait un combat sans effet, c'est-a-dire la meme
     * impression d'ecran fige qu'on cherche a supprimer.
     */
    public function testItStaysHiddenUntilTheQuestIsAccepted(): void
    {
        $quest = $this->quest(['monsters' => [['slug' => 'training_dummy_still', 'count' => 1]]]);

        $this->guide->method('currentQuest')->willReturn($quest);
        $this->guide->method('hasAccepted')->willReturn(false);

        self::assertNull($this->offer->pendingFor(new Player()));
    }

    /**
     * Les huit autres etapes de l'acte I ne demandent aucun mannequin : le
     * bouton n'a rien a faire sur leur ecran.
     */
    public function testItStaysHiddenOnAStepThatAsksForSomethingElse(): void
    {
        $quest = $this->quest(['gesture' => [['gesture' => 'gather', 'count' => 3]]]);

        $this->guide->method('currentQuest')->willReturn($quest);
        $this->guide->method('hasAccepted')->willReturn(true);

        self::assertNull($this->offer->pendingFor($this->player($quest)));
    }

    public function testAFinishedArcOffersNothing(): void
    {
        $this->guide->method('currentQuest')->willReturn(null);

        self::assertNull($this->offer->pendingFor(new Player()));
    }

    /**
     * **Le garde-fou qui compte.** Ce chemin pose un combat en zone sure, ou
     * `safe: true` interdit toute rencontre. Si le slug d'une quete designait
     * un vrai monstre, l'offre doit refuser — le lanceur le refuse aussi, et
     * un garde-fou qui n'existe qu'a un bout d'une chaine finit par etre
     * contourne par l'autre.
     */
    public function testARealMonsterIsNeverOffered(): void
    {
        $quest = $this->quest(['monsters' => [['slug' => 'training_dummy_still', 'count' => 1]]]);
        $wolf = $this->dummy('training_dummy_still', null);

        $this->guide->method('currentQuest')->willReturn($quest);
        $this->guide->method('hasAccepted')->willReturn(true);
        $this->monsters->method('findOneBy')->willReturn($wolf);

        self::assertNull($this->offer->pendingFor($this->player($quest)));
    }

    /**
     * @param array<string, mixed> $requirements
     */
    private function quest(array $requirements): Quest
    {
        $quest = new Quest();
        $quest->setName('Bapteme du feu');
        $quest->setDescription('...');
        $quest->setRequirements($requirements);

        return $quest;
    }

    private function dummy(string $slug, ?TrainingMode $mode): Monster
    {
        $monster = new Monster();
        $monster->setSlug($slug);
        $monster->setName('Mannequin');
        $monster->setTrainingMode($mode);

        return $monster;
    }

    private function player(Quest $quest): Player
    {
        $player = new Player();

        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer($player);
        $playerQuest->setQuest($quest);
        $player->getQuests()->add($playerQuest);

        return $player;
    }
}
