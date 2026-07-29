<?php

namespace App\Tests\Unit\DataFixtures;

use App\Enum\CraftSpecialization;
use App\Enum\MateriaSlotType;
use PHPUnit\Framework\TestCase;

/**
 * Le contrat du plan des domaines (DOM-08).
 *
 * Les sept jalons precedents ont chacun leur test. Celui-ci tient les invariants
 * **transverses** — ceux qu'aucun jalon ne possede, et qui se cassent donc en
 * silence quand un jalon suivant deplace une piece.
 *
 * Cinq lois, une par decision fondatrice de GAME_DOMAINS :
 *
 * 1. **Aucun sort actif par competence** (regle 9 du projet, § 1 du document).
 * 2. **Aucun passif non borne** : un nœud qui donne des statistiques de combat
 *    appartient a un domaine, sinon il s'applique partout (DOM-01).
 * 3. **Le savoir n'est jamais borne par le build** : rien dans l'acquisition ne
 *    lit l'equipement (DOM-02, § 1).
 * 4. **Le plancher de sertissage jour 1** : les pieces d'entree acceptent tout
 *    (DOM-03).
 * 5. **L'accord d'hybride est pose partout et ouvert nulle part** (DOM-07).
 */
class DomainPlanContractTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    private function skills(): string
    {
        return (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');
    }

    // =====================================================================
    // 1. Aucun sort actif par competence
    // =====================================================================

    /**
     * Regle 9 : une competence d'arbre n'accorde **jamais** de sort actif.
     *
     * Un sort s'obtient par une materia — competence `materia.unlock`, materia
     * possedee, materia sertie — et par l'attaque de base de l'arme. Le chemin
     * `actions.combat.spell_slug` a ete ferme dans `CombatSkillResolver` ; ce
     * test ferme la porte cote **donnees**, la ou un nœud pourrait le rouvrir.
     */
    public function testNoSkillGrantsAnActiveSpell(): void
    {
        self::assertStringNotContainsString(
            "'spell_slug'",
            $this->skills(),
            'Un nœud d\'arbre accorde un sort actif. Les competences sont passives : un sort s\'obtient par une '
            . 'materia (regle 9).',
        );
    }

    // =====================================================================
    // 2. Aucun passif non borne
    // =====================================================================

    /**
     * Tout nœud qui donne des statistiques de combat appartient a un domaine.
     *
     * DOM-01 borne les passifs par la case `element x registre` **du domaine**.
     * Un nœud sans domaine tombe dans la clause de retro-compatibilite et
     * s'applique donc partout — ce qui etait le comportement d'avant le jalon.
     * Un seul nœud oublie suffit a rouvrir la breche, et rien ne le signale.
     */
    public function testEverySkillWithCombatStatsBelongsToADomain(): void
    {
        $orphans = [];
        $current = null;
        $stats = false;
        $domain = false;

        foreach (explode("\n", $this->skills()) as $line) {
            if (preg_match("/^            '([a-z_0-9]+)' => \[$/", $line, $match) === 1) {
                if ($current !== null && $stats && !$domain) {
                    $orphans[] = $current;
                }
                $current = $match[1];
                $stats = false;
                $domain = false;

                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match("/'(damage|heal|hit|critical|life)' => [1-9]/", $line) === 1) {
                $stats = true;
            }
            if (str_contains($line, "'domain' =>")) {
                $domain = true;
            }
        }

        if ($current !== null && $stats && !$domain) {
            $orphans[] = $current;
        }

        self::assertSame(
            [],
            $orphans,
            'Ces nœuds donnent des statistiques de combat sans appartenir a un domaine : leur passif s\'applique '
            . 'a toute action, ce que DOM-01 existe pour empecher.',
        );
    }

    // =====================================================================
    // 3. Le savoir n'est jamais borne par le build
    // =====================================================================

    /**
     * L'acquisition d'une competence ne lit jamais l'equipement.
     *
     * « Le savoir n'est jamais borne. Le faire est borne par l'instant »
     * (§ 1). DOM-02 borne l'**expression** d'un arbre par ce qu'on porte ; le
     * jour ou l'acquisition s'y mettrait aussi, un joueur perdrait l'acces a un
     * nœud en rangeant une arme, et la doctrine des trois couches s'effondrerait
     * sur elle-meme.
     */
    public function testAcquiringASkillNeverReadsTheBuild(): void
    {
        $helper = (string) file_get_contents($this->root() . '/src/Helper/PlayerSkillHelper.php');
        $acquiring = (string) file_get_contents($this->root() . '/src/GameEngine/Progression/SkillAcquiring.php');

        foreach (['BuildDomainResolver', 'getInventories', 'CombatScope'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $helper, sprintf('L\'acquisition lit le build via "%s".', $forbidden));
            self::assertStringNotContainsString($forbidden, $acquiring, sprintf('L\'acquisition lit le build via "%s".', $forbidden));
        }
    }

    // =====================================================================
    // 4. Le plancher de sertissage jour 1
    // =====================================================================

    /**
     * Les pieces d'entree acceptent n'importe quelle materia.
     *
     * « Les kits T1 portent au moins un emplacement libre : la premiere materia
     * se sertit toujours, quelle que soit la tenue » (§ 3, garde-fou 2). C'est
     * le plancher que DOM-03 a laisse intact en ne typant que le tissu au-dessus
     * du palier 1.
     */
    public function testEntryTierGearAcceptsAnyMateria(): void
    {
        $gear = (string) file_get_contents($this->root() . '/fixtures/game/item/gear_item.yaml');

        $current = null;
        $typed = [];
        foreach (explode("\n", $gear) as $line) {
            if (preg_match("/^\s+slug: '([a-z0-9-]+)'/", $line, $match) === 1) {
                $current = $match[1];

                continue;
            }
            if ($current !== null && preg_match("/^\s+materia_slot_type: '([a-z]+)'/", $line, $match) === 1) {
                $typed[$current] = $match[1];
            }
        }

        foreach (['linen-hood', 'linen-robe', 'linen-gloves'] as $entry) {
            self::assertArrayNotHasKey(
                $entry,
                $typed,
                sprintf('La piece d\'entree "%s" a ete typee : le plancher jour 1 ne tient plus.', $entry),
            );
        }

        self::assertSame(
            MateriaSlotType::Free,
            MateriaSlotType::from('free'),
            'Le type par defaut a change de nom : verifier que l\'absence de declaration vaut toujours « libre ».',
        );
    }

    // =====================================================================
    // 5. L'accord d'hybride
    // =====================================================================

    /**
     * Les vingt-quatre arbres de combat portent leur accord reserve, et aucun
     * n'est ouvert.
     *
     * Un accord ouvert accorderait une materia d'un element qui n'existe pas
     * encore : le nœud s'apprendrait, et n'accorderait rien.
     */
    public function testEveryCombatTreeCarriesADormantHybridAccord(): void
    {
        $accords = $this->hybridAccordTable();

        self::assertCount(24, $accords, 'Les vingt-quatre arbres de combat ne sont plus tous declares dans la table des accords.');

        $skills = $this->skills();
        self::assertStringContainsString("'dormant' => true", $skills, 'Aucun accord reserve n\'est pose.');
        self::assertStringContainsString("'action' => 'materia.hybrid'", $skills, 'L\'accord reserve ne declare pas son element parent.');
    }

    /**
     * La table des accords, lue **dans le corps de la methode qui la porte**.
     *
     * Les accords sont generes plutot qu'ecrits vingt-quatre fois : chercher un
     * slug litteral dans le fichier ne trouverait rien, et le test passerait en
     * verifiant le vide. C'est le defaut que ce fichier existe pour traquer — il
     * n'a pas le droit d'en etre porteur.
     *
     * @return array<string, string> domaine => element parent
     */
    private function hybridAccordTable(): array
    {
        $skills = $this->skills();

        $start = strpos($skills, 'private function getDormantHybridAccords(): array');
        self::assertNotFalse($start, 'La table des accords d\'hybride a disparu.');

        $end = strpos($skills, '$accords = [];', $start);
        self::assertNotFalse($end, 'Le corps de la table des accords est introuvable.');

        preg_match_all(
            "/'([a-z]+)' => '(fire|water|air|earth|metal|beast|light|dark)'/",
            substr($skills, $start, $end - $start),
            $matches,
            \PREG_SET_ORDER,
        );

        $accords = [];
        foreach ($matches as $match) {
            $accords[$match[1]] = $match[2];
        }

        return $accords;
    }

    /**
     * Aucun metier ne porte d'accord d'hybride.
     *
     * La fusion est une affaire de materia, et les metiers n'en ont pas
     * (regle 9). Leur en poser un ferait entrer l'artisanat dans le combat par
     * la porte de derriere — la meme faute que DOM-01 refuse pour les registres.
     */
    public function testNoCraftOrGatheringTreeCarriesAnAccord(): void
    {
        $crafts = array_map(static fn (CraftSpecialization $c): string => $c->value, CraftSpecialization::cases());
        $gathering = ['miner', 'herbalist', 'fisherman', 'skinner', 'lumberjack'];

        $intruders = array_values(array_intersect(
            array_keys($this->hybridAccordTable()),
            array_merge($crafts, $gathering),
        ));

        self::assertSame([], $intruders, 'Ces metiers portent un accord d\'hybride : la fusion est une affaire de materia.');
    }
}
