<?php

namespace App\Tests\Unit\GameEngine\Bestiary;

use App\Entity\App\Player;
use App\Entity\App\PlayerBestiary;
use App\Entity\Game\Monster;
use App\Entity\Game\Race;
use App\GameEngine\Bestiary\BestiaryRevealPolicy;
use App\GameEngine\Race\RaceCapabilityResolver;
use PHPUnit\Framework\TestCase;

/**
 * Le flair de l'Orc (ONB-07b).
 *
 * GAME_ONBOARDING § 4.5 : element et faiblesse d'un monstre lisibles **des la
 * premiere rencontre**, sans attendre le palier de dix mises a mort.
 *
 * La loi qui rend la capacite sure n'est pas qu'elle marche : c'est **ce
 * qu'elle ne fait pas**. Elle touche ce qu'on **sait**, jamais ce qu'on
 * **produit** — ni degats, ni points de vie, ni butin, ni cout, ni nombre
 * d'actions, ni prix. Elle avance la lecture d'une information que tout le
 * monde finit par obtenir, et c'est tout.
 */
class BestiaryRevealPolicyTest extends TestCase
{
    /**
     * Sans flair, le palier fait foi.
     */
    public function testWithoutTheScentTheTierDecides(): void
    {
        $policy = new BestiaryRevealPolicy(new RaceCapabilityResolver());

        self::assertFalse($policy->weaknessesRevealed($this->entry('elf', 1)));
        self::assertTrue($policy->weaknessesRevealed($this->entry('elf', PlayerBestiary::TIER_WEAKNESSES)));
    }

    /**
     * Avec le flair, la premiere rencontre suffit.
     */
    public function testTheScentReadsFromTheFirstEncounter(): void
    {
        $policy = new BestiaryRevealPolicy(new RaceCapabilityResolver());

        self::assertTrue($policy->weaknessesRevealed($this->entry('orc', 0)));
    }

    /**
     * Le palier n'est pas supprime, il est **double**.
     *
     * « Dix mises a mort revelent les faiblesses » reste vrai pour tout le
     * monde, Orcs compris. Retirer le palier pour eux aurait fait de la race une
     * condition d'acces au contenu — ce que ce jalon existe pour eviter.
     */
    public function testTheTierSurvivesForEveryone(): void
    {
        self::assertSame(10, PlayerBestiary::TIER_WEAKNESSES);
        self::assertTrue($this->entry('orc', PlayerBestiary::TIER_WEAKNESSES)->hasWeaknessesRevealed());
    }

    /**
     * Un joueur sans peuple n'est pas une erreur.
     *
     * Un personnage ancien, une fixture minimale : il ne voit simplement rien
     * de plus que les autres.
     */
    public function testAPlayerWithoutAPeopleSeesNothingMore(): void
    {
        $policy = new BestiaryRevealPolicy(new RaceCapabilityResolver());

        self::assertFalse($policy->readsByScent(new Player()));
        self::assertFalse($policy->readsByScent(null));
    }

    /**
     * La liste ne retient que ce qui est lisible.
     *
     * Le gabarit consulte cette liste plutot que de rejouer la regle a chacun
     * des deux endroits qui l'affichent : une condition maintenue en deux
     * exemplaires finit toujours par mentir d'un cote.
     */
    public function testOnlyReadableMonstersAreListed(): void
    {
        $policy = new BestiaryRevealPolicy(new RaceCapabilityResolver());

        $seen = $this->entry('elf', 1, 7);
        $hunted = $this->entry('elf', PlayerBestiary::TIER_WEAKNESSES, 9);

        self::assertSame([9], $policy->readableMonsterIds([$seen, $hunted]));
    }

    /**
     * La capacite ne touche **que** la lecture.
     *
     * Le controle porte sur la source : le service ne doit jamais ecrire ni
     * lire une quantite. C'est par la qu'un « petit bonus de butin pour les
     * Orcs » entrerait, et il ne se verrait dans aucun scenario avant d'avoir
     * desequilibre la chasse.
     */
    public function testTheCapabilityNeverTouchesWhatIsProduced(): void
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 4) . '/src/GameEngine/Bestiary/BestiaryRevealPolicy.php');

        foreach (['setLife', 'setDamage', 'addGils', 'setQuantity', 'setPrice', 'ActionEnergy', 'yield'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, sprintf(
                "Le flair touche « %s » : une capacite de peuple change ce qu'on **sait**, jamais ce qu'on **produit**.",
                $forbidden,
            ));
        }
    }

    private function entry(string $raceSlug, int $kills, int $monsterId = 1): PlayerBestiary
    {
        $race = new Race();
        $race->setSlug($raceSlug);
        $race->setName($raceSlug);

        $player = new Player();
        $player->setRace($race);

        $monster = $this->createConfiguredMock(Monster::class, ['getId' => $monsterId]);

        $entry = new PlayerBestiary($player, $monster);
        for ($i = 0; $i < $kills; ++$i) {
            $entry->incrementKillCount();
        }

        return $entry;
    }
}
