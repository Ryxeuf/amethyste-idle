<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\GameEngine\Progression\CombatBranchCatalog;
use App\GameEngine\Progression\CombatBranchManager;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * L'exclusivite de la fourche, en jeu (ARC-14b).
 *
 * ARC-14a a declare les fourches ; rien ne les lisait. Ce jalon leur donne un
 * porteur — une ligne **par arbre**, jamais une par personnage : mener les
 * vingt-quatre arbres de combat reste permis, et le renoncement se joue *dans*
 * l'arbre, jamais entre eux (GAME_DOMAINS § 1).
 */
class CombatBranchManagerTest extends AbstractIntegrationTestCase
{
    /**
     * Un joueur choisit sa branche, et le choix se retrouve.
     */
    public function testAPlayerChoosesABranchAndItSticks(): void
    {
        $manager = $this->manager();
        $player = $this->getPlayer();
        $pyromancy = $this->domain('Pyromancien');

        self::assertNull($player->getCombatBranchFor($pyromancy), 'Un arbre commence sans fourche tranchee.');

        $choice = $manager->choose($player, $pyromancy, 'ember');

        self::assertNotNull($choice);
        self::assertSame('ember', $player->getCombatBranchFor($pyromancy)?->getBranch());
    }

    /**
     * Une branche que le catalogue ne connait pas est refusee.
     *
     * Le refus est une **reponse**, jamais une exception : un ecran doit
     * pouvoir le dire sans que la requete tombe. Et sans lui, un nœud
     * conditionne a une chaine mal orthographiee serait a jamais
     * inapprenable — le joueur chercherait un prerequis qui n'existe pas.
     */
    public function testAnUnknownBranchIsRefused(): void
    {
        $manager = $this->manager();

        self::assertFalse($manager->canChoose($this->domain('Pyromancien'), 'inferno'));
        self::assertNull($manager->choose($this->getPlayer(), $this->domain('Pyromancien'), 'inferno'));
    }

    /**
     * Un arbre sans fourche n'a rien a trancher.
     *
     * Les vingt arbres qu'ARC-08 doit encore forker : laisser choisir une
     * branche qui ne mene nulle part serait pire que refuser.
     */
    public function testATreeWithoutAForkHasNothingToChoose(): void
    {
        self::assertFalse($this->manager()->canChoose($this->domain('Assassin'), 'ember'));
    }

    /**
     * **L'exclusivite est dans l'arbre, jamais entre les arbres.**.
     *
     * La lecon de DOM-04 : le modele livre pour les metiers portait une
     * specialisation unique pour tout le personnage, si bien que choisir
     * Forgeron fermait a jamais le Tanneur. Choisir la Braise chez le
     * Pyromancien ne doit rien fermer chez l'Archer.
     */
    public function testChoosingInOneTreeClosesNothingInAnother(): void
    {
        $manager = $this->manager();
        $player = $this->getPlayer();

        $manager->choose($player, $this->domain('Pyromancien'), 'ember');
        $manager->choose($player, $this->domain('Archer'), 'volley');

        self::assertSame('ember', $player->getCombatBranchFor($this->domain('Pyromancien'))?->getBranch());
        self::assertSame('volley', $player->getCombatBranchFor($this->domain('Archer'))?->getBranch());
    }

    /**
     * Le respec deplace le choix, il n'en cree pas un second.
     *
     * L'exclusivite est tenue par le schema — contrainte unique
     * `(player, domain)` —, mais elle doit aussi se lire dans le code : deux
     * lignes pour un meme arbre voudraient dire deux branches apprises.
     */
    public function testASwitchMovesTheChoiceInsteadOfAddingOne(): void
    {
        $manager = $this->manager();
        $player = $this->getPlayer();
        $pyromancy = $this->domain('Pyromancien');

        $manager->choose($player, $pyromancy, 'ember');
        $manager->choose($player, $pyromancy, 'flare');

        $forThisTree = array_filter(
            $player->getCombatBranches()->toArray(),
            fn ($choice) => $choice->getDomain() === $pyromancy,
        );

        self::assertCount(1, $forThisTree);
        self::assertSame('flare', $player->getCombatBranchFor($pyromancy)?->getBranch());
    }

    /**
     * L'ecran peut dire a quoi on renonce, et pas seulement ce qu'on gagne.
     *
     * *Une fourche dont on ne lit qu'un cote n'est pas un choix, c'est un
     * bouton.* Et ce qui rend le renoncement concret est le **geste** de
     * l'autre branche : mesure au § 9 bis, ce sont les accords qui separent
     * les branches, jamais les passifs.
     */
    public function testTheScreenCanSayWhatIsGivenUp(): void
    {
        $forgone = $this->manager()->forgoneBy($this->domain('Pyromancien'), 'ember');

        self::assertNotNull($forgone);
        self::assertSame('L\'Éclat', $forgone['label']);
        self::assertSame('Nova de feu', $forgone['accord']);
    }

    /**
     * **Le pont entre les deux identifiants de domaine tient.**.
     *
     * Le projet en a deux, et c'est la source d'erreur sur laquelle ce jalon a
     * bute : la cle de fixture est anglaise (`pyromancy`, celle
     * qu'`equipment_ports.yaml` emploie deja) quand `Domain::getSlug()` derive
     * du titre francais. Le catalogue garde la cle anglaise comme ses voisins
     * et fait le pont par le **libelle** — qui doit donc etre, exactement, le
     * titre d'un arbre de combat reel.
     *
     * Sans ce test, renommer un arbre casserait sa fourche **en silence** : le
     * catalogue continuerait de charger, `keyForLabel()` rendrait `null`, et la
     * branche deviendrait simplement inchoisissable.
     */
    public function testEveryForkedTreeLabelNamesARealCombatTree(): void
    {
        $catalog = new CombatBranchCatalog(\dirname(__DIR__, 3));
        $titles = [];
        foreach ($this->em->getRepository(Domain::class)->findAll() as $domain) {
            if ($domain->getRegister() !== null) {
                $titles[] = $domain->getTitle();
            }
        }

        foreach ($catalog->trees() as $key => $tree) {
            self::assertContains(
                $tree['label'],
                $titles,
                sprintf('La fourche "%s" nomme un arbre de combat qui n\'existe pas : %s.', $key, $tree['label'])
            );
            self::assertSame($key, $catalog->keyForLabel($tree['label']));
        }
    }

    /**
     * Le service, construit a la main.
     *
     * Le conteneur l'inline tant qu'aucun controleur ne l'injecte, et le rendre
     * public pour un test le ferait exister en production sans raison.
     */
    private function manager(): CombatBranchManager
    {
        return new CombatBranchManager(
            new CombatBranchCatalog(\dirname(__DIR__, 3)),
            $this->em,
        );
    }

    private function domain(string $title): Domain
    {
        foreach ($this->em->getRepository(Domain::class)->findAll() as $domain) {
            if ($domain->getTitle() === $title) {
                return $domain;
            }
        }

        self::fail(sprintf('Domaine "%s" introuvable.', $title));
    }
}
