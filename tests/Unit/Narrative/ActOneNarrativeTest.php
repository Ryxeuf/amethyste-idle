<?php

namespace App\Tests\Unit\Narrative;

use PHPUnit\Framework\TestCase;

/**
 * L'acte I raconte quelque chose, et le raconte avec les mots du canon (NAR-20).
 *
 * ONB-12b a livre les dix etapes : elles enseignaient parfaitement la boucle du
 * jeu et **ne racontaient rien**. Un joueur pouvait finir le tutoriel sans avoir
 * lu une fois qui il est, sur quoi le village est bati, ni ce qu'il ramasse.
 *
 * Ce fichier verifie la **fiction**, la ou `ActOneChainTest` verifie la forme —
 * la separation est deliberee : NAR-20 reecrit des textes, et ne doit pas
 * pouvoir casser l'enchainement en le faisant. Ce qu'on tient ici :
 *
 * 1. **Les trois mots du canon sont dits.** Le Limpide (qui vous etes), la
 *    Voute (sur quoi le Fanal est bati), le Fanal (ou vous vous reveillez).
 *    GAME_WORLD § 7.2 : « Le reveil du Limpide ».
 * 2. **Aucun ancien nom ne subsiste.** La loi de nommage vaut aussi pour la
 *    narration ; un texte d'acte I est ce qu'un joueur lit en premier.
 * 3. **Le teaser du Cristal est dit deux fois, jamais plus.** Une replique de
 *    PNJ et une entree de Codex — le plan borne le fil a cela : l'acte I le
 *    plante, il ne le tire pas.
 */
class ActOneNarrativeTest extends TestCase
{
    private function fixture(string $name): string
    {
        $source = file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/' . $name);
        self::assertIsString($source, sprintf('%s est illisible.', $name));

        return $source;
    }

    /**
     * Le joueur apprend qui il est : un Limpide.
     */
    public function testThePlayerIsNamedALimpide(): void
    {
        $quests = $this->fixture('QuestFixtures.php');

        self::assertStringContainsString('un Limpide', $quests, 'L\'acte I ne dit jamais au joueur ce qu\'il est (GAME_WORLD § 7.2).');
        self::assertStringContainsString('a Limpide', $quests, 'La version anglaise de l\'acte I ne nomme pas le Limpide.');
    }

    /**
     * La Voute est nommee, et situee sous le Fanal.
     */
    public function testTheVaultIsNamedAndPlaced(): void
    {
        $quests = $this->fixture('QuestFixtures.php');

        self::assertStringContainsString('bâti sur la Voûte', $quests, 'L\'acte I ne montre jamais la Voute : le meta-arc n\'a pas de point d\'accroche.');
        self::assertStringContainsString('upon the Vault', $quests, 'La version anglaise ne place pas la Voute sous le Fanal.');
    }

    /**
     * Le teaser du Cristal est porte par une replique **et** une entree de Codex.
     *
     * Les deux, parce qu'un dialogue de marchand se saute : le Codex est le seul
     * endroit ou le fil se relit. Pas davantage, parce que le plan le borne — un
     * fil repete a chaque etape cesse d'etre un fil.
     */
    public function testTheCrystalHookIsPlantedTwice(): void
    {
        self::assertStringContainsString(
            'la même matière que le Cristal sous la Voûte',
            $this->fixture('VillageHubPnjFixtures.php'),
            'Aucun PNJ du Fanal ne fait le lien entre l\'amethyste ramassee et le Cristal.',
        );

        $codex = $this->fixture('CodexEntryFixtures.php');

        self::assertStringContainsString("'slug' => 'la-voute'", $codex, 'Le Codex n\'a pas d\'entree sur la Voute.');
        self::assertStringContainsString('même matière que ce Cristal', $codex, 'L\'entree de Codex sur la Voute ne porte pas le teaser.');
    }

    /**
     * L'entree de Codex sur la Voute s'ouvre en arrivant au Fanal.
     *
     * Une entree `manual` ne se debloque jamais : elle serait ecrite pour
     * personne. Le hub est le seul lieu que tout personnage traverse.
     */
    public function testTheVaultCodexEntryUnlocksOnArrival(): void
    {
        $codex = $this->fixture('CodexEntryFixtures.php');

        // Du debut de l'entree au debut de la suivante : les `],` internes
        // (traductions, tableaux imbriques) rendraient toute autre borne fausse.
        $entry = substr($codex, (int) strpos($codex, "'slug' => 'la-voute'"));
        $next = strpos($entry, "'slug' => ", 1);
        $entry = false === $next ? $entry : substr($entry, 0, $next);

        self::assertStringContainsString('UNLOCK_ZONE_VISIT', $entry, 'L\'entree sur la Voute ne se debloque pas a l\'arrivee au Fanal.');
        self::assertStringContainsString("'unlockKey' => 'village-de-lumiere'", $entry, 'L\'entree sur la Voute n\'est rattachee a aucune zone.');
    }

    /**
     * Aucun texte de fixture ne porte plus les noms d'avant la loi de nommage.
     */
    public function testNoNarrativeTextKeepsTheOldNames(): void
    {
        $forbidden = ['Village de Lumière', 'Village of Light', 'Sanctuaire de Lumière', 'Confrérie des Ombres'];

        foreach (['QuestFixtures.php', 'VillageHubPnjFixtures.php', 'CodexEntryFixtures.php', 'RegionFixtures.php', 'Game/FactionFixtures.php'] as $name) {
            $source = $this->fixture($name);

            foreach ($forbidden as $old) {
                self::assertStringNotContainsString($old, $source, sprintf('%s porte encore « %s » (loi de nommage, GAME_WORLD § 1).', $name, $old));
            }
        }
    }
}
