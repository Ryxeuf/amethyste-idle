<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\Entity\Game\FactionReward;
use App\Enum\ReputationTier;
use App\GameEngine\Reputation\PatronageBonusResolver;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Les bonus de la faction qu'on porte, et d'elle seule (FAC-01).
 *
 * Deux choses se verrouillent ici, et elles vont ensemble. Les bonus de palier
 * **s'appliquent enfin** — ils ne l'etaient nulle part, le systeme livre les
 * affichait et s'arretait la. Et ils ne s'appliquent **que pour le patron** :
 * sans quoi le jalon aurait rendu quatre factions cumulables la ou il n'y en
 * avait aucune.
 */
class PatronageBonusResolverTest extends TestCase
{
    public function testWithoutAPatronNothingApplies(): void
    {
        $player = new Player();

        $resolver = $this->resolver($player, $this->knightRewards(), 20000);

        self::assertSame(0, $resolver->maxLifePercent($player));
        self::assertSame(
            ['damage' => 100, 'heal' => 0, 'hit' => 40, 'critical' => 0, 'life' => 0],
            $resolver->amplify($player, ['damage' => 100, 'heal' => 0, 'hit' => 40, 'critical' => 0, 'life' => 0]),
            'Sans couleurs portees, les bonus de combat ne bougent pas d\'un point.',
        );
    }

    /**
     * Le patron accorde le bonus du plus haut palier atteint — un seul.
     *
     * « Un palier de reputation ouvre des portes ; il n'empile jamais de la
     * puissance » : a Exalte, on prend le bonus d'Exalte, pas la somme d'Ami,
     * Honore et Exalte.
     */
    public function testOnlyTheHighestReachedTierCounts(): void
    {
        $player = new Player();
        $chevaliers = $this->faction('chevaliers');
        $player->setPatronFaction($chevaliers);

        $percents = $this->resolver($player, $this->knightRewards(), 20000)->percentsFor($player);

        // Exalte : +15 % de degats et +10 % de precision — et rien d'Ami ni
        // d'Honore, dont les +5 % et +10 % n'ont pas a s'y ajouter.
        self::assertSame(15, $percents['damage']);
        self::assertSame(10, $percents['hit']);
        self::assertSame(0, $percents['life'], 'Le +10 % de vie d\'Honore ne s\'empile pas sur le palier d\'Exalte.');
    }

    /**
     * Un palier qu'on n'a pas atteint n'accorde rien.
     */
    public function testATierNotYetReachedGrantsNothing(): void
    {
        $player = new Player();
        $player->setPatronFaction($this->faction('chevaliers'));

        // Ami tout juste : le bonus d'Ami, jamais celui d'Honore.
        $percents = $this->resolver($player, $this->knightRewards(), 2000)->percentsFor($player);

        self::assertSame(5, $percents['damage']);
        self::assertSame(0, $percents['life']);
    }

    /**
     * L'amplification porte sur les bonus de combat, jamais sur la vie.
     *
     * Les points de vie maximum se calculent sur la base du personnage, dans
     * `PlayerEffectiveStatsCalculator`. Les amplifier ici aussi les compterait
     * deux fois — un defaut qui ne se verrait que sur une barre de vie trop
     * longue, et que personne ne relierait aux factions.
     */
    public function testAmplifyLeavesLifeToTheStatsCalculator(): void
    {
        $player = new Player();
        $player->setPatronFaction($this->faction('chevaliers'));

        $amplified = $this->resolver($player, $this->knightRewards(), 20000)
            ->amplify($player, ['damage' => 100, 'heal' => 0, 'hit' => 40, 'critical' => 0, 'life' => 200]);

        self::assertSame(115, $amplified['damage'], '+15 % sur un bonus de 100 fait 115.');
        self::assertSame(44, $amplified['hit'], '+10 % sur un bonus de 40 fait 44.');
        self::assertSame(200, $amplified['life'], 'La vie ne doit pas etre amplifiee ici.');
    }

    /**
     * Un bonus nul reste nul.
     *
     * Un pourcentage de rien vaut rien : arrondir vers le haut donnerait un
     * point de degat a un personnage qui n'en a aucun, et le patronage
     * deviendrait une source de puissance a lui seul.
     */
    public function testAPercentOfNothingIsNothing(): void
    {
        $player = new Player();
        $player->setPatronFaction($this->faction('chevaliers'));

        $amplified = $this->resolver($player, $this->knightRewards(), 20000)
            ->amplify($player, ['damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0]);

        self::assertSame(['damage' => 0, 'heal' => 0, 'hit' => 0, 'critical' => 0, 'life' => 0], $amplified);
    }

    /**
     * Une faction que le joueur n'a jamais rencontree n'accorde rien.
     *
     * Le cas arrive apres une suppression de reputation, ou sur un personnage
     * neuf dont le patronage aurait ete pose par une donnee heritee.
     */
    public function testAPatronWithoutReputationGrantsNothing(): void
    {
        $player = new Player();
        $player->setPatronFaction($this->faction('chevaliers'));

        $resolver = $this->resolver($player, $this->knightRewards(), null);

        self::assertSame(0, $resolver->percentsFor($player)['damage']);
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function faction(string $slug): Faction
    {
        return (new Faction())->setSlug($slug)->setName($slug);
    }

    /**
     * Les recompenses de statistiques livrees pour l'Ordre des Chevaliers.
     *
     * @return list<FactionReward>
     */
    private function knightRewards(): array
    {
        $rewards = [];

        foreach ([
            [ReputationTier::Ami, ['stat' => 'damage', 'percent' => 5]],
            [ReputationTier::Honore, ['stat' => 'life', 'percent' => 10]],
            [ReputationTier::Exalte, ['stat' => 'damage', 'percent' => 15, 'extra_stat' => 'hit', 'extra_percent' => 10]],
        ] as [$tier, $data]) {
            $reward = new FactionReward();
            $reward->setRequiredTier($tier);
            $reward->setRewardType('stat_bonus');
            $reward->setRewardData($data);
            $reward->setLabel('bonus');
            $rewards[] = $reward;
        }

        return $rewards;
    }

    /**
     * @param list<FactionReward> $rewards
     */
    private function resolver(Player $player, array $rewards, ?int $reputation): PatronageBonusResolver
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn($rewards);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $reputationManager = $this->createMock(ReputationManager::class);
        $reputationManager->method('getPlayerFaction')->willReturnCallback(
            function (Player $subject, Faction $faction) use ($reputation): ?PlayerFaction {
                if ($reputation === null) {
                    return null;
                }

                $playerFaction = new PlayerFaction();
                $playerFaction->setFaction($faction);
                $playerFaction->setReputation($reputation);

                return $playerFaction;
            },
        );

        return new PatronageBonusResolver($entityManager, $reputationManager);
    }
}
