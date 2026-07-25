<?php

namespace App\Controller\Game;

use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Event\Game\PnjDialogEvent;
use App\Helper\PlayerHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Dialogue avec un PNJ present dans la zone courante (ZON-27b).
 *
 * Les dialogues PNJ etaient un overlay de la carte, supprime avec ZON-21a :
 * plus aucun ecran n'y menait, `PnjDialogEvent` n'avait plus d'emetteur, et les
 * objectifs de quete « parler a un PNJ » (`talk_to`) ne progressaient plus.
 *
 * Rendu server-rendered, dans la lignee du pivot PBBG : un noeud de dialogue par
 * page, les choix sont des liens. Les choix `open_shop` renvoient vers la
 * boutique du PNJ ; les autres avancent au noeud suivant.
 */
#[Route('/game/pnj')]
class PnjTalkController extends AbstractController
{
    public function __construct(
        private readonly PlayerHelper $playerHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route('/{id}/talk', name: 'app_game_pnj_talk', methods: ['GET'])]
    public function talk(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $player = $this->playerHelper->getPlayer();
        if (null === $player) {
            return $this->redirectToRoute('app_game');
        }

        $pnj = $this->entityManager->getRepository(Pnj::class)->find($id);
        if (!$pnj instanceof Pnj || !$this->isReachableFrom($pnj, $player)) {
            throw $this->createNotFoundException('Personnage introuvable');
        }

        $dialog = $pnj->getDialog();
        $nodeIndex = max(0, $request->query->getInt('node'));
        if ($nodeIndex >= \count($dialog)) {
            $nodeIndex = 0;
        }

        // La rencontre elle-meme fait progresser les quetes `talk_to`, quel que
        // soit le noeud consulte : c'est le fait de parler au PNJ qui compte.
        $this->eventDispatcher->dispatch(new PnjDialogEvent($player, $pnj), PnjDialogEvent::NAME);

        $node = $dialog[$nodeIndex] ?? null;

        return $this->render('game/pnj/talk.html.twig', [
            'pnj' => $pnj,
            'node' => $node,
            'nodeIndex' => $nodeIndex,
            'hasNextNode' => isset($dialog[$nodeIndex + 1]),
            'choices' => \is_array($node['choices'] ?? null) ? $node['choices'] : [],
        ]);
    }

    /**
     * Un PNJ n'est joignable que depuis sa zone (meme regle que la boutique,
     * ZON-27a). Les PNJ sans zone (donnees heritees) restent joignables.
     */
    private function isReachableFrom(Pnj $pnj, Player $player): bool
    {
        $pnjZone = $pnj->getZone();
        if (null === $pnjZone) {
            return true;
        }

        return $player->getCurrentZone()?->getId() === $pnjZone->getId();
    }
}
