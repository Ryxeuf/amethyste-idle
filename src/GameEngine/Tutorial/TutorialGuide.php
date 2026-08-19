<?php

namespace App\GameEngine\Tutorial;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\Game\Quest;
use App\Enum\TutorialStep;
use App\Repository\PlayerQuestCompletedRepository;
use App\Repository\QuestRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * La destination vient de l'arc, exactement comme l'etape.
 *
 * **Le defaut repare.** `TutorialStep::hintRoute()` rendait `app_game_zone`
 * pour quatre etapes sur cinq. L'ecran de zone etant celui d'ou l'on lit le
 * bandeau, le lien ne menait nulle part — et comme il ne disait ni la zone ni
 * la personne, un joueur envoye « chez la maitresse d'armes » n'avait aucun
 * moyen de savoir ou elle se tient. Le bandeau nommait un but sans donner de
 * chemin.
 *
 * **Pourquoi l'etape ne suffisait pas.** Les cinq etapes sont une *vue* de
 * l'arc `intro`, qui en compte dix (ONB-14) : « L'arme » couvre deux quetes,
 * « La materia » trois. Une etape ne peut donc pas designer un interlocuteur —
 * elle en couvre plusieurs. La quete, elle, en designe **un**, et le nomme dans
 * ses `requirements`. C'est la seule source qui sache repondre « ou ».
 *
 * On lit donc la **prochaine quete non terminee de l'arc**, et on en derive le
 * lieu, la personne et la route. Deplacer un PNJ, renommer une zone ou reecrire
 * la chaine deplace le guide avec — rien n'est recopie.
 */
class TutorialGuide
{
    /**
     * L'ecran ou se joue chaque geste de l'acte I.
     *
     * La table est **fermee et verifiee en CI** : `ActOneGuidanceTest` refuse
     * qu'un geste de la chaine n'ait pas d'ecran. A l'execution en revanche,
     * un geste inconnu retombe sur la route de l'etape plutot que de lever —
     * un bandeau d'aide qui casse la page est pire que le silence qu'il repare.
     *
     * @var array<string, array{0: string, 1: string}> geste => [route, clef du libelle]
     */
    private const GESTURE_SCREENS = [
        // L'ecran d'equipement, et pas l'inventaire : c'est la qu'on porte une
        // arme **et** qu'on sertit une materia (`_materia_track` y est inclus).
        // « Ouvrir l'inventaire » aurait laisse le joueur devant quatre onglets.
        'equip_item' => ['app_game_inventory_equipment_list', 'equip'],
        'socket_materia' => ['app_game_inventory_equipment_list', 'socket'],
        'cast_spell' => ['app_game_zone', 'dummy'],
        'gather' => ['app_game_zone', 'gather'],
        'craft_item' => ['app_game_craft', 'craft'],
        'travel' => ['app_game_world_map', 'travel'],
        'start_expedition' => ['app_game_zone', 'expedition'],
    ];

    /**
     * Les routes que ce service peut emettre, toutes branches confondues.
     *
     * Enumerees ici plutot que recopiees dans le test qui les verifie : c'est
     * une liste ecrite a la main a partir d'un nom de route, et une faute de
     * frappe n'y fait pas d'erreur — elle fait un lien qui rend 404. Le premier
     * jet visait `app_game_inventory`, qui n'existe pas : la route s'appelle
     * `app_game_inventory_equipment_list`.
     *
     * @return list<string>
     */
    public static function emittableRoutes(): array
    {
        $routes = ['app_game_quests', 'app_game_pnj_talk', 'app_game_world_map'];

        foreach (self::GESTURE_SCREENS as [$route]) {
            $routes[] = $route;
        }

        foreach (TutorialStep::cases() as $step) {
            $routes[] = $step->hintRoute();
        }

        return array_values(array_unique($routes));
    }

    /**
     * Les clefs de libelle que ce service peut emettre.
     *
     * Meme raison : un libelle manquant ne casse rien, il affiche sa propre
     * clef au joueur — `game.onboarding.hint.action.talk` sous le bandeau.
     *
     * @return list<string>
     */
    public static function emittableActionKeys(): array
    {
        $keys = ['accept', 'talk', 'travel_to'];

        foreach (self::GESTURE_SCREENS as [, $actionKey]) {
            $keys[] = $actionKey;
        }

        foreach (TutorialStep::cases() as $step) {
            $keys[] = 'step_' . strtolower($step->name);
        }

        return array_values(array_unique($keys));
    }

    /**
     * Quetes terminees, memorisees le temps de la requete.
     *
     * Le bandeau est rendu sur chaque page du jeu et pose trois fois la meme
     * question (l'etape, la quete courante, le repli) : sans memo, un joueur en
     * tutoriel paierait trois `COUNT` par page pour une valeur qui ne peut pas
     * changer entre-temps.
     *
     * Une `WeakMap` et non un tableau indexe : `spl_object_id` n'est unique que
     * parmi les objets **vivants**, donc un identifiant libere puis reattribue
     * servirait le compte d'un autre joueur.
     *
     * @var \WeakMap<Player, int>
     */
    private \WeakMap $completedCache;

    public function __construct(
        private readonly QuestRepository $questRepository,
        private readonly PlayerQuestCompletedRepository $completedRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->completedCache = new \WeakMap();
    }

    /**
     * La prochaine quete de l'arc, ou `null` quand il est termine.
     *
     * L'arc est strictement sequentiel (chaque etape a la precedente en
     * prerequis), donc le rang de la prochaine quete **est** le nombre de
     * quetes terminees — la meme deduction que `TutorialStep`, et pour la meme
     * raison : deux compteurs finiraient par diverger.
     */
    public function currentQuest(Player $player): ?Quest
    {
        $quests = $this->questRepository->findByStoryArc(TutorialManager::ARC);

        return $quests[$this->completedSteps($player)] ?? null;
    }

    private function completedSteps(Player $player): int
    {
        // Indexe sur l'instance et non sur l'identifiant : `Player::getId()`
        // n'est pas nullable et leve sur une entite pas encore persistee, et
        // Doctrine rend de toute facon la meme instance dans une requete.
        if (isset($this->completedCache[$player])) {
            return $this->completedCache[$player];
        }

        $completed = $this->completedRepository->countCompletedInArc($player, TutorialManager::ARC);
        $this->completedCache[$player] = $completed;

        return $completed;
    }

    public function destinationFor(Player $player, ?string $locale = null): ?TutorialDestination
    {
        $quest = $this->currentQuest($player);
        if (null === $quest) {
            return null;
        }

        // **Le premier obstacle du jeu, et il n'etait dit nulle part.** Aucune
        // quete de l'acte I n'est acceptee d'office a la creation du
        // personnage : le journal d'un nouveau venu est vide. Or les objectifs
        // ne progressent que sur une quete acceptee — parler a Ysold sans
        // l'avoir acceptee ne compte pas. Le tutoriel paraissait alors gele
        // pendant qu'on faisait exactement ce qu'il demandait.
        if (!$this->hasAccepted($player, $quest)) {
            return new TutorialDestination(
                route: 'app_game_quests',
                routeParams: [],
                actionKey: 'accept',
                actionParams: ['%quest%' => $quest->getLocalizedName($locale)],
            );
        }

        $requirements = $quest->getRequirements();

        return $this->towardsPnj($player, $requirements, $locale)
            ?? $this->towardsDummy($requirements)
            ?? $this->towardsGesture($requirements)
            ?? $this->towardsStep($player);
    }

    /**
     * Le dernier recours : la route large de l'etape.
     *
     * Elle ne dit ni le lieu ni la personne — c'est precisement ce que ce
     * service repare —, mais elle mene quelque part. Un bandeau prive de son
     * lien parce qu'une exigence de quete a une forme inattendue serait une
     * regression sur le defaut qu'on corrige.
     */
    private function towardsStep(Player $player): ?TutorialDestination
    {
        $step = TutorialStep::fromCompletedSteps($this->completedSteps($player));

        return null === $step ? null : new TutorialDestination(
            route: $step->hintRoute(),
            routeParams: [],
            actionKey: 'step_' . strtolower($step->name),
        );
    }

    /**
     * « Parler a quelqu'un » — la seule exigence qui porte un lieu.
     *
     * @param array<string, mixed> $requirements
     */
    private function towardsPnj(Player $player, array $requirements, ?string $locale): ?TutorialDestination
    {
        $talkTo = $requirements['talk_to'][0] ?? null;
        if (!\is_array($talkTo)) {
            return null;
        }

        $pnj = $this->findPnj($talkTo);
        if (!$pnj instanceof Pnj) {
            return null;
        }

        $pnjZone = $pnj->getZone();
        $place = $pnjZone?->getLocalizedName($locale);
        $person = $pnj->getLocalizedName($locale);

        // Un PNJ n'est joignable que depuis sa zone (meme regle que la
        // boutique, ZON-27a). Envoyer sur son dialogue depuis ailleurs
        // donnerait un 404 — donc on envoie d'abord vers la carte, et le
        // libelle dit quelle zone rejoindre.
        //
        // Un PNJ **sans zone** reste joignable de partout (donnees heritees,
        // rattachement en attente). Le cas n'est pas theorique : un habitant
        // mal rattache disparait de l'ecran de zone, qui liste strictement par
        // zone — c'est ainsi que la maitresse d'armes s'etait retrouvee absente
        // de l'ecran ou l'acte I l'envoie. Le guide le mene a elle quand meme.
        $reachable = null === $pnjZone || $player->getCurrentZone()?->getId() === $pnjZone->getId();

        if (!$reachable) {
            return new TutorialDestination(
                route: 'app_game_world_map',
                routeParams: [],
                actionKey: 'travel_to',
                actionParams: ['%place%' => (string) $place],
                place: $place,
                person: $person,
            );
        }

        return new TutorialDestination(
            route: 'app_game_pnj_talk',
            routeParams: ['id' => (int) $pnj->getId()],
            actionKey: 'talk',
            actionParams: ['%person%' => $person],
            place: $place,
            person: $person,
        );
    }

    /**
     * Le mannequin d'entrainement, qui n'est pas une rencontre.
     *
     * Il n'appartient a aucune zone (`TrainingFightLauncher`) : il n'y a donc
     * pas de lieu a nommer, seulement l'ecran d'ou on le dresse.
     *
     * @param array<string, mixed> $requirements
     */
    private function towardsDummy(array $requirements): ?TutorialDestination
    {
        $monster = $requirements['monsters'][0] ?? null;
        if (!\is_array($monster) || !\is_string($monster['slug'] ?? null)) {
            return null;
        }

        if (!str_starts_with($monster['slug'], 'training_dummy')) {
            return null;
        }

        return new TutorialDestination(
            route: 'app_game_zone',
            routeParams: [],
            actionKey: 'dummy',
        );
    }

    /**
     * @param array<string, mixed> $requirements
     */
    private function towardsGesture(array $requirements): ?TutorialDestination
    {
        $gesture = $requirements['gesture'][0]['gesture'] ?? null;
        if (!\is_string($gesture) || !isset(self::GESTURE_SCREENS[$gesture])) {
            return null;
        }

        [$route, $actionKey] = self::GESTURE_SCREENS[$gesture];

        return new TutorialDestination(
            route: $route,
            routeParams: [],
            actionKey: $actionKey,
        );
    }

    /**
     * @param array<string, mixed> $talkTo
     */
    private function findPnj(array $talkTo): ?Pnj
    {
        $repository = $this->entityManager->getRepository(Pnj::class);

        $id = $talkTo['pnj_id'] ?? null;
        // `pnj_id` vaut 0 dans les fixtures de quete, recale apres flush par
        // `QuestChainFixtures` : une base ou le recalage n'a pas tourne rendrait
        // 0, qui ne designe personne.
        if (\is_int($id) && $id > 0) {
            $pnj = $repository->find($id);
            if ($pnj instanceof Pnj) {
                return $pnj;
            }
        }

        // Repli par le nom porte dans la quete : c'est ce que le joueur lit, et
        // c'est ce qui reste quand l'identifiant n'a pas ete recale.
        $name = $talkTo['name'] ?? null;

        return \is_string($name) && '' !== $name ? $repository->findOneBy(['name' => $name]) : null;
    }

    /**
     * Cette quete est-elle au journal du joueur ?
     *
     * Publique parce que `TrainingDummyOffer` pose la meme question, et qu'une
     * regle recopiee derive de son original en silence.
     */
    public function hasAccepted(Player $player, Quest $quest): bool
    {
        foreach ($player->getQuests() as $playerQuest) {
            if ($playerQuest->getQuest()->getId() === $quest->getId()) {
                return true;
            }
        }

        return false;
    }
}
