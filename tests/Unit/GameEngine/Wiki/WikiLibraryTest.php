<?php

namespace App\Tests\Unit\GameEngine\Wiki;

use App\GameEngine\Wiki\WikiLibrary;
use PHPUnit\Framework\TestCase;

/**
 * Le wiki joueur est navigable, et il ne ment pas sur lui-meme (WIK-02).
 *
 * Les 25 pages de `docs/wiki/` sont ecrites en markdown et se citent en chemins
 * relatifs, ce qui les rend lisibles telles quelles dans le depot. Servies, ces
 * memes pages doivent mener quelque part : la reecriture des liens n'est pas une
 * commodite, c'est la condition pour que le wiki soit un wiki.
 *
 * Ce fichier tient deux proprietes qu'aucune relecture humaine ne tient
 * longtemps — une page ajoutee sans lien, un lien vers une page renommee. Ni
 * l'une ni l'autre ne casse quoi que ce soit a l'execution : elles se voient
 * seulement quand un joueur clique.
 */
class WikiLibraryTest extends TestCase
{
    private function library(): WikiLibrary
    {
        return new WikiLibrary(\dirname(__DIR__, 4));
    }

    /**
     * Chaque fichier du wiki est atteignable par une route.
     *
     * Le sommaire est **constate** depuis les dossiers, jamais recopie : une
     * page deposee dans un chapitre existant apparait donc toute seule. Ce test
     * protege le cas inverse — un fichier range quelque part qu'aucune section
     * ne balaye, et que personne ne lira jamais.
     */
    public function testEveryPageIsReachable(): void
    {
        $library = $this->library();

        $routed = [WikiLibrary::HOME => true];
        foreach ($library->sections() as $section) {
            foreach ($section->pages as $page) {
                $routed[$section->slug . '/' . $page->slug . '.md'] = true;
            }
        }

        $orphans = array_values(array_diff(array_keys($library->allFiles()), array_keys($routed)));

        self::assertSame([], $orphans, 'Ces pages du wiki n\'ont aucune route : personne ne les lira jamais.');
        self::assertGreaterThan(20, \count($library->allFiles()), 'Le wiki n\'a pas ete lu : le test ne verifie rien.');
    }

    /**
     * Aucun lien interne ne pointe dans le vide.
     *
     * C'est le test que le jalon demande explicitement, et la raison est simple :
     * un lien casse ne leve rien, ne journalise rien, et ne se voit qu'au clic.
     * Une page renommee sans ses referents est donc une regression parfaitement
     * silencieuse — celle qui survit le plus longtemps.
     */
    public function testNoInternalLinkPointsNowhere(): void
    {
        $library = $this->library();

        $broken = [];
        foreach ($library->internalLinks() as $link) {
            if (null === $library->resolve($link['link'], $link['section'])) {
                $broken[] = $link['from'] . ' → ' . $link['link'];
            }
        }

        self::assertNotEmpty($library->internalLinks(), 'Aucun lien lu : le test ne verifie rien.');
        self::assertSame([], $broken, 'Ces liens du wiki ne menent nulle part.');
    }

    /**
     * Un lien entre voisins de chapitre se resout.
     *
     * C'est la moitie des liens du wiki — une page qui en cite une autre du meme
     * dossier ecrit son nom nu. Les traiter comme des liens absolus les aurait
     * tous casses, et le test precedent l'aurait dit ; celui-ci nomme le cas pour
     * qu'on ne le re-casse pas en « simplifiant » la resolution.
     */
    public function testASiblingLinkResolvesWithinItsSection(): void
    {
        $library = $this->library();

        self::assertSame(
            '/wiki/01-commencer/energie',
            $library->resolve('energie.md', '01-commencer'),
        );
        self::assertNull($library->resolve('energie.md'));
    }

    /**
     * Le sommaire ramene a la racine du wiki, pas a un fichier.
     */
    public function testTheSummaryLinkGoesToTheWikiRoot(): void
    {
        self::assertSame('/wiki', $this->library()->resolve('../README.md', '03-le-monde'));
    }

    /**
     * Une adresse inventee ne trouve rien — et n'a touche aucun disque.
     *
     * Les deux segments d'URL sont des **clefs** dans un index construit en
     * lisant le dossier, pas des morceaux de chemin : il n'y a donc rien a
     * assainir, parce qu'il n'y a rien a interpreter.
     */
    public function testAnInventedAddressFindsNothing(): void
    {
        $library = $this->library();

        self::assertNull($library->page('01-commencer', 'page-qui-n-existe-pas'));
        self::assertNull($library->page('chapitre-invente', 'energie'));
        self::assertNull($library->page('..', '..'));
    }

    /**
     * Le wiki ne documente aucun systeme qui n'existe pas (WIK-03).
     *
     * C'est le contrat d'entretien, et sa moitie verifiable. Un wiki qui decrit
     * une mecanique non livree est pire qu'un wiki incomplet : le joueur la
     * cherche, ne la trouve pas, et cesse de faire confiance au reste — y
     * compris a ce qui est exact.
     *
     * La liste ne nomme que des systemes **actes et non livres** : leurs plans
     * existent, leur code non. Elle est volontairement courte — chaque entree
     * doit pouvoir se justifier par un jalon ouvert, sans quoi ce test
     * deviendrait un interdit de vocabulaire.
     *
     * Le jour ou l'un d'eux est livre, son entree quitte cette liste **dans le
     * meme changement** : c'est ce qui rend la mise a jour du wiki visible au
     * moment de la livraison, plutot que six mois plus tard.
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function undeliveredSystems(): array
    {
        return [
            ['caravane', 'ECO-32→35, Piste I : l\'affretement entre Bourgs n\'existe pas encore'],
            ['affretement', 'ECO-32→35 : idem, sous son autre nom'],
            ['Repertoire des gestes', 'REP-01→06 : le dernier systeme acte sans une ligne de code'],
        ];
    }

    /**
     * @dataProvider undeliveredSystems
     */
    public function testTheWikiNeverDescribesAnUndeliveredSystem(string $needle, string $why): void
    {
        $offenders = [];
        foreach ($this->library()->allFiles() as $relative => $absolute) {
            $content = (string) file_get_contents($absolute);
            if (stripos($content, $needle) !== false) {
                $offenders[] = $relative;
            }
        }

        self::assertSame(
            [],
            $offenders,
            sprintf('Le wiki decrit « %s », qui n\'est pas livre (%s). Un joueur le chercherait en vain.', $needle, $why),
        );
    }

    /**
     * Le libelle d'un chapitre vient du sommaire, avec ses accents.
     *
     * Le deriver du nom de dossier rendrait « Regles dor », et un wiki qui
     * ecorche ses propres titres perd la seule chose qu'il vend : le soin.
     */
    public function testSectionTitlesAreReadFromTheSummary(): void
    {
        $sections = $this->library()->sections();

        self::assertArrayHasKey('07-regles-dor', $sections);
        self::assertSame('Les règles d\'or', $sections['07-regles-dor']->title);
    }

    /**
     * Le titre d'une page vient de la page elle-meme.
     *
     * Un tableau de libelles a cote des fichiers finirait par annoncer autre
     * chose que ce que la page dit — et c'est le seul defaut qui compte pour un
     * document dont l'interet est de ne pas mentir.
     */
    public function testPageTitlesComeFromTheDocumentItself(): void
    {
        $pages = $this->library()->sections()['01-commencer']->pages;

        self::assertArrayHasKey('energie', $pages);
        self::assertNotSame('', $pages['energie']->title);
        self::assertStringNotContainsString('#', $pages['energie']->title);
    }
}
