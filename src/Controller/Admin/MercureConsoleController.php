<?php

namespace App\Controller\Admin;

use App\Service\AdminLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/mercure', name: 'admin_mercure_')]
#[IsGranted('ROLE_ADMIN')]
class MercureConsoleController extends AbstractController
{
    private const KNOWN_TOPICS = [
        'map/move' => 'Mouvements joueurs sur la carte',
        'map/respawn' => 'Respawn de mobs sur la carte',
        'chat/global' => 'Chat global',
        'chat/map' => 'Chat de carte',
        'chat/private' => 'Messages prives',
        'game/notification' => 'Notifications en jeu',
        'admin/broadcast' => 'Broadcast admin',
    ];

    public function __construct(
        private readonly HubInterface $hub,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/mercure/index.html.twig', [
            'topics' => self::KNOWN_TOPICS,
        ]);
    }

    #[Route('/publish', name: 'publish', methods: ['POST'])]
    public function publish(Request $request): Response
    {
        $topic = $request->request->get('topic', '');
        $data = $request->request->get('data', '');

        if (empty($topic)) {
            $this->addFlash('error', 'Le topic est obligatoire.');

            return $this->redirectToRoute('admin_mercure_index');
        }

        // Validate JSON if it looks like JSON
        if (str_starts_with(trim($data), '{') || str_starts_with(trim($data), '[')) {
            $decoded = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addFlash('error', 'JSON invalide : ' . json_last_error_msg());

                return $this->redirectToRoute('admin_mercure_index');
            }
        }

        try {
            $update = new Update($topic, $data);
            $this->hub->publish($update);

            $this->adminLogger->log('mercure_publish', 'Mercure', null, 'Topic: ' . $topic, [
                'topic' => $topic,
                'data_length' => strlen($data),
            ]);

            $this->addFlash('success', 'Message publie sur "' . $topic . '" avec succes.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Mercure : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_mercure_index');
    }
}
