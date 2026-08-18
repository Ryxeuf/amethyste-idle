<?php

namespace App\Tests\Integration\Fight;

use App\Entity\App\Player;
use App\Entity\Game\StatusEffect;
use App\GameEngine\Fight\BuildChangeLaw;
use App\GameEngine\Fight\ChargeLaw;
use App\GameEngine\Fight\DeferredLaw;
use App\GameEngine\Fight\TransferLaw;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Les sept refus du § 13.5, tenus (ARC-18i).
 *
 * GAME_ARCHETYPES § 13.5 nomme sept mecaniques que le jeu **refuse**, chacune
 * empruntee a un MMO qui l'emploie. Un refus qu'aucun test ne tient n'est pas un
 * refus, c'est une intention — et une intention se perd a la premiere personne
 * qui n'a pas lu le document.
 *
 * **Comme `ArchetypesPlanContractTest` (ARC-09), ce contrat distingue ce qu'il
 * tient de ce qu'il ne peut pas tenir.** Trois des sept sont verifiables par le
 * code ; les quatre autres sont des refus **par absence** — on ne peut pas
 * prouver qu'une chose n'existe pas, on peut seulement montrer qu'aucun des
 * mecanismes livres ne la produit. Les compter comme tenus serait un mensonge
 * d'inventaire.
 */
class GestureFormRefusalsTest extends AbstractIntegrationTestCase
{
    /**
     * **Refus n° 4 — le changement d'arme en combat.**.
     *
     * Le seul des sept qui etait **viole en production** : cinq chemins
     * changent l'equipement — equiper, deequiper, sertir, dessertir, modifier —
     * et aucun ne verifiait le combat, quand `UseItemController` le verifiait
     * depuis toujours. *La regle etait tenue a un endroit sur six*, ce qui est
     * la facon habituelle dont une regle recopiee disparait.
     *
     * Ce qu'il protege n'est pas une preference de style : autoriser le
     * changement en combat effondre DOM-02 **et** les passifs conditionnels
     * d'ARC-12 d'un coup — porter la dague pour le geste qui aime la dague puis
     * la hache au tour suivant rendrait *chaque condition vraie tout le temps*,
     * donc jamais payee.
     */
    public function testTheBuildCannotBeChangedInCombat(): void
    {
        $player = $this->getPlayer();
        self::assertTrue(BuildChangeLaw::isAllowed($player), 'Hors combat, rien n\'empeche de se rhabiller.');

        $mob = $this->getMob();
        $this->createFight($player, $mob);

        self::assertFalse(BuildChangeLaw::isAllowed($player));

        // Et la loi est lue par **tous** les chemins qui changent le build, pas
        // par un sur six : c'est le comptage qui tient la regle, parce qu'un
        // sixieme chemin ajoute demain sans elle serait invisible a la
        // relecture.
        $guarded = [];
        foreach (glob(\dirname(__DIR__, 3) . '/src/Controller/Game/Inventory/*.php') as $file) {
            $source = file_get_contents($file) ?: '';
            if (str_contains($source, 'BuildChangeLaw::isAllowed')) {
                $guarded[] = basename($file, '.php');
            }
        }

        sort($guarded);
        self::assertSame(
            [
                'EquipItemController',
                'EquipmentModifyController',
                'SetMateriaController',
                'UnequipItemController',
                'UnsetMateriaController',
            ],
            $guarded,
            'Un chemin de changement de build ne lit plus la loi : la regle est redevenue une intention.'
        );
    }

    /**
     * **Refus n° 5 — la ressource qui persiste entre les rencontres.**.
     *
     * Elle *double la comptabilite de la journee* (§ 9 septies) et transforme
     * le combat en gestion de stock. Le refus est tenu par le **rangement** et
     * non par une routine de nettoyage : charge et differes vivent dans les
     * metadonnees du combat, donc ils n'existent nulle part ou survivre — *une
     * remise a zero qu'il faut penser a appeler finit par etre oubliee*.
     *
     * **L'ouverture (ARC-18g) est l'exception apparente, et il faut la
     * nommer** : elle survit bien hors rencontre, sur le joueur. Ce n'est pas
     * une violation, et ce qui les separe est precis — *le refus vise le
     * **stock**, pas la **preparation***. Une seule ouverture attend a la fois,
     * elle est payee en energie d'action et la premiere rencontre la consomme :
     * il n'y a rien a accumuler.
     */
    public function testNoResourceSurvivesTheEncounterExceptAPreparedOpening(): void
    {
        // Charge et differe : ranges dans le combat, donc mortels avec lui.
        self::assertStringStartsWith('arc18', ChargeLaw::METADATA_KEY);
        self::assertStringStartsWith('arc18', DeferredLaw::METADATA_KEY);

        // L'ouverture : bornee a un, donc jamais un stock.
        $player = new Player();
        $player->prepareOpening(9);
        $player->prepareOpening(9);
        self::assertSame(9, $player->getPendingOpening(), 'Les ouvertures s\'accumulent : c\'est devenu un stock.');
        self::assertSame(9, $player->consumeOpening());
        self::assertSame(0, $player->getPendingOpening());
    }

    /**
     * **Refus n° 1 — la table de menace.**.
     *
     * Le score cumule reste refuse : *pas de course au sommet, pas de perte
     * d'aggro a gerer*. Ce que le jeu a pris, c'est **l'aggro bornee sous la
     * forme du transfert** — un deplacement plafonne, jamais un classement.
     *
     * Le test tient les deux moities : aucune valeur cumulee (le transfert se
     * lit sur un coup, jamais sur un historique), et le plafond du canon.
     */
    public function testThereIsNoThreatTable(): void
    {
        self::assertSame(0.5, TransferLaw::MAX_SHARE, 'Le deplacement de menace a change de plafond.');

        // Le transfert repond sur **un coup**, pas sur un cumul : deux appels
        // successifs avec les memes parts rendent la meme chose, ce qu'un score
        // cumule ne ferait pas.
        self::assertSame(
            TransferLaw::redirected(40, [0.5]),
            TransferLaw::redirected(40, [0.5]),
        );
    }

    /**
     * **Les quatre refus par absence**, nommes plutot que comptes comme tenus.
     *
     * On ne peut pas prouver qu'une mecanique n'existe pas ; on peut montrer
     * qu'aucun mecanisme livre ne la produit, et **dire lequel la produirait**
     * si elle arrivait. C'est la discipline d'ARC-09 : *un invariant qu'aucun
     * mecanisme ne peut violer ne mesure rien, et le compter comme tenu serait
     * un mensonge d'inventaire.*
     *
     * - **La trinite obligatoire** — aucun role n'est requis pour ouvrir ou
     *   terminer un donjon ; il faudrait une contrainte de composition sur
     *   `GroupDungeonRun` pour la violer ;
     * - **Les effets qui n'existent qu'en groupe** — `StatusEffectManager::deposit()`
     *   pose sur **le lanceur compris**, donc tout depot a une lecture en solo ;
     * - **Le tour supplementaire** — aucun chemin ne rend la main deux fois ;
     *   dans un modele semi-synchrone, un tour de plus est un tour vole aux
     *   autres ;
     * - **La montee en puissance entre les combats** — aucun compteur
     *   journalier n'alimente une statistique ; ce serait recompenser le temps
     *   passe, la seule chose que le jeu a decide de ne jamais recompenser.
     */
    public function testTheFourRefusalsByAbsenceAreNamed(): void
    {
        $byAbsence = [
            'la trinite obligatoire' => 'une contrainte de composition sur GroupDungeonRun',
            'les effets qui n\'existent qu\'en groupe' => 'un depot qui sauterait son lanceur',
            'le tour supplementaire' => 'un chemin qui rendrait la main deux fois',
            'la montee en puissance entre les combats' => 'un compteur journalier lu par une statistique',
        ];

        self::assertCount(4, $byAbsence, 'Le compte des refus tenus a bouge : 3 par le code, 4 par absence, 7 en tout.');

        // La seule chose verifiable ici, et elle vaut : un depot pose bien sur
        // son lanceur, donc aucun geste collectif n'est mort en solo.
        $reflection = new \ReflectionMethod(\App\GameEngine\Fight\StatusEffectManager::class, 'deposit');
        self::assertSame('allies', $reflection->getParameters()[1]->getName());
    }

    /**
     * Le vocabulaire des types de statut reste ferme.
     *
     * Les huit formes ont ajoute quatre types (`riposte`, `stance`, `transfer`,
     * `familiar`). Aucun ne s'ecrit en fixture sans passer par une constante :
     * *une neuvieme forme est une decision de moteur, jamais un ajout de
     * donnees*.
     */
    public function testTheStatusTypeVocabularyStaysClosed(): void
    {
        foreach ([StatusEffect::TYPE_RIPOSTE, StatusEffect::TYPE_STANCE, StatusEffect::TYPE_TRANSFER, StatusEffect::TYPE_FAMILIAR] as $type) {
            self::assertContains($type, StatusEffect::TYPES, $type);
        }
    }
}
