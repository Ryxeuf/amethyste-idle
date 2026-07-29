<?php

namespace App\Controller\Game;

use App\Entity\App\GameEvent;
use App\Entity\App\Player;
use App\Entity\Game\Monster;
use App\GameEngine\GameMaster\GameMasterAnimationService;
use App\GameEngine\GameMaster\GameMasterJournal;
use App\GameEngine\GameMaster\GameMasterRestrictionException;
use App\GameEngine\Social\ChatManager;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Console du maitre du jeu — les outils d'animation, dans le jeu.
 *
 * L'ecran existe pour une raison simple : pendant une soiree, `/admin` est un
 * autre monde. Changer d'onglet pour lancer un evenement, c'est quitter la zone
 * ou l'on anime. Tout ce qui est ici est donc **contextuel a la zone courante**
 * du MJ, et rien de plus n'y a sa place — la gestion de fond reste a l'admin.
 *
 * L'acces ne se decide pas sur un role de compte mais sur le drapeau du
 * personnage : c'est le personnage qui anime, et un membre du staff connecte sur
 * son perso ordinaire n'a rien a faire ici.
 */
#[Route('/game/gm', name: 'app_game_gm_')]
class GameMasterController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly GameMasterAnimationService $animation,
        private readonly GameMasterJournal $journal,
        private readonly ChatManager $chatManager,
    ) {
    }

    #[Route('', name: 'console', methods: ['GET'])]
    public function console(): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        $zone = $player->getCurrentZone();

        return $this->render('game/game_master/console.html.twig', [
            'player' => $player,
            'zone' => $zone,
            'zoneEvents' => null !== $zone ? $this->animation->eventsForZone($zone) : [],
            'monsters' => $this->entityManager->getRepository(Monster::class)
                ->createQueryBuilder('m')->orderBy('m.name', 'ASC')->getQuery()->getResult(),
            'maxSpawn' => GameMasterAnimationService::MAX_SPAWN_COUNT,
            'gmMessages' => array_reverse($this->chatManager->getGameMasterHistory(30)),
            'announcements' => $this->chatManager->getAnnouncementHistory(10),
        ]);
    }

    /**
     * Bascule le mode incognito. Reversible a volonte : c'est un vetement, pas
     * une decision.
     */
    #[Route('/incognito', name: 'incognito', methods: ['POST'])]
    public function incognito(Request $request): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        if (!$this->isCsrfTokenValid('gm_incognito', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $enabled = !$player->isGameMasterIncognito();
        $player->setGameMasterIncognito($enabled);
        $this->entityManager->flush();

        $this->journal->record($player, 'incognito', $enabled ? 'passe incognito' : 'redevient visible', [
            'enabled' => $enabled,
            'zone' => $player->getCurrentZone()?->getSlug(),
        ]);
        $this->addFlash('success', $enabled
            ? 'Vous n\'apparaissez plus dans les ecrans des joueurs.'
            : 'Vous etes de nouveau visible.');

        return $this->redirectToRoute('app_game_gm_console');
    }

    /**
     * Annonce globale : la voix du monde, entendue de tous sans abonnement.
     */
    #[Route('/announce', name: 'announce', methods: ['POST'])]
    public function announce(Request $request): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        if (!$this->isCsrfTokenValid('gm_announce', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $content = trim((string) $request->request->get('content', ''));
        $message = $this->chatManager->sendGameMasterAnnouncement($player, $content);

        if (null === $message) {
            $this->addFlash('error', 'Annonce vide ou trop longue.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $this->journal->record($player, 'announce', $message->getContent());
        $this->addFlash('success', 'Annonce diffusee.');

        return $this->redirectToRoute('app_game_gm_console');
    }

    /**
     * Canal de service : deux animateurs se coordonnent sans passer par le
     * canal global ni par des messages prives a deux.
     */
    #[Route('/say', name: 'say', methods: ['POST'])]
    public function say(Request $request): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        if (!$this->isCsrfTokenValid('gm_say', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $this->chatManager->sendGameMasterMessage($player, trim((string) $request->request->get('content', '')));

        return $this->redirectToRoute('app_game_gm_console');
    }

    /**
     * Fait apparaitre des monstres dans la zone ou se tient le MJ.
     */
    #[Route('/spawn', name: 'spawn', methods: ['POST'])]
    public function spawn(Request $request): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        if (!$this->isCsrfTokenValid('gm_spawn', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $zone = $player->getCurrentZone();
        if (null === $zone) {
            $this->addFlash('error', 'Vous n\'etes dans aucune zone.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $monster = $this->entityManager->getRepository(Monster::class)->find($request->request->getInt('monster_id'));
        if (!$monster instanceof Monster) {
            $this->addFlash('error', 'Monstre introuvable.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        try {
            $spawned = $this->animation->spawnMonsters($player, $zone, $monster, $request->request->getInt('count', 1));
            $this->addFlash('success', sprintf('%d × %s dans %s.', \count($spawned), $monster->getName(), $zone->getName()));
        } catch (GameMasterRestrictionException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_gm_console');
    }

    /**
     * Lance ou clot un evenement de la zone courante.
     */
    #[Route('/event/{id}/{action}', name: 'event', requirements: ['id' => '\d+', 'action' => 'launch|stop'], methods: ['POST'])]
    public function event(int $id, string $action, Request $request): Response
    {
        $player = $this->requireGameMaster();
        if (!$player instanceof Player) {
            return $player;
        }

        if (!$this->isCsrfTokenValid('gm_event_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        $event = $this->entityManager->getRepository(GameEvent::class)->find($id);
        if (!$event instanceof GameEvent) {
            $this->addFlash('error', 'Evenement introuvable.');

            return $this->redirectToRoute('app_game_gm_console');
        }

        try {
            if ('launch' === $action) {
                $this->animation->launchEvent($player, $event);
                $this->addFlash('success', sprintf('« %s » est lance.', $event->getName()));
            } else {
                $this->animation->stopEvent($player, $event);
                $this->addFlash('success', sprintf('« %s » est clos.', $event->getName()));
            }
        } catch (GameMasterRestrictionException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_game_gm_console');
    }

    /**
     * Le personnage connecte, s'il est maitre du jeu ; sinon la redirection a
     * renvoyer telle quelle.
     *
     * Un joueur ordinaire est redirige plutot que refuse : la console n'est pas
     * un secret, elle ne le concerne pas.
     */
    private function requireGameMaster(): Player|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        if (!$player->isGameMaster()) {
            return $this->redirectToRoute('app_game');
        }

        return $player;
    }
}
