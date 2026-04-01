<?php

namespace App\Controller\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\World;
use App\Form\Admin\MobSpawnType;
use App\Form\Admin\PnjPositionType;
use App\GameEngine\Terrain\MapFactory;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/maps', name: 'admin_map_')]
#[IsGranted('ROLE_WORLD_BUILDER')]
class MapController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
        private readonly MapFactory $mapFactory,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Map::class)->createQueryBuilder('m');

        if ($search) {
            $qb->where('LOWER(m.name) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('m.name', 'ASC');
        $maps = $qb->getQuery()->getResult();

        $mapStats = [];
        foreach ($maps as $map) {
            $mapStats[$map->getId()] = [
                'players' => $this->em->getRepository(Player::class)->count(['map' => $map]),
                'mobs' => $this->em->getRepository(Mob::class)->count(['map' => $map]),
                'pnjs' => $this->em->getRepository(Pnj::class)->count(['map' => $map]),
                'portals' => $this->em->getRepository(ObjectLayer::class)->count(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]),
            ];
        }

        return $this->render('admin/map/index.html.twig', [
            'maps' => $maps,
            'mapStats' => $mapStats,
            'search' => $search,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        $worlds = $this->em->getRepository(World::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name', ''));
            $width = $request->request->getInt('width', 40);
            $height = $request->request->getInt('height', 30);
            $worldId = $request->request->getInt('world_id');

            $errors = [];
            if ($name === '') {
                $errors[] = 'Le nom est requis.';
            }
            if ($width < 10 || $width > 200) {
                $errors[] = 'La largeur doit etre entre 10 et 200.';
            }
            if ($height < 10 || $height > 200) {
                $errors[] = 'La hauteur doit etre entre 10 et 200.';
            }

            $world = $worldId ? $this->em->getRepository(World::class)->find($worldId) : null;
            if (!$world) {
                $errors[] = 'Le monde est requis.';
            }

            $existing = $this->em->getRepository(Map::class)->findOneBy(['name' => $name]);
            if ($existing) {
                $errors[] = 'Une carte avec ce nom existe deja.';
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }

                return $this->render('admin/map/create.html.twig', [
                    'worlds' => $worlds,
                    'name' => $name,
                    'width' => $width,
                    'height' => $height,
                    'worldId' => $worldId,
                ]);
            }

            $map = $this->mapFactory->createBlankMap($name, $width, $height, $world);

            $this->adminLogger->log('create', 'Map', $map->getId(), 'Carte vierge creee : ' . $name . ' (' . $width . 'x' . $height . ')');
            $this->addFlash('success', 'Carte "' . $name . '" creee (' . $width . 'x' . $height . '). Ouvrez l\'editeur pour commencer.');

            return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
        }

        return $this->render('admin/map/create.html.twig', [
            'worlds' => $worlds,
            'name' => '',
            'width' => 40,
            'height' => 30,
            'worldId' => null,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Map $map): Response
    {
        $players = $this->em->getRepository(Player::class)->findBy(['map' => $map]);
        $mobs = $this->em->getRepository(Mob::class)->findBy(['map' => $map]);
        $pnjs = $this->em->getRepository(Pnj::class)->findBy(['map' => $map]);
        $portals = $this->em->getRepository(ObjectLayer::class)->findBy(['map' => $map, 'type' => ObjectLayer::TYPE_PORTAL]);

        return $this->render('admin/map/show.html.twig', [
            'map' => $map,
            'players' => $players,
            'mobs' => $mobs,
            'pnjs' => $pnjs,
            'portals' => $portals,
        ]);
    }

    // --- Mob Spawn Management ---

    #[Route('/{id}/mobs/new', name: 'mob_new', requirements: ['id' => '\d+'])]
    public function mobNew(Request $request, Map $map): Response
    {
        $mob = new Mob();
        $mob->setMap($map);
        $mob->setLife(1);
        $mob->setLevel(1);

        $form = $this->createForm(MobSpawnType::class, $mob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mob->setLife($mob->getMonster()->getLife());
            $mob->setCreatedAt(new \DateTime());
            $mob->setUpdatedAt(new \DateTime());
            $this->em->persist($mob);
            $this->em->flush();

            $this->adminLogger->log('create', 'Mob', $mob->getId(), $mob->getMonster()->getName() . ' sur ' . $map->getName());
            $this->addFlash('success', 'Mob "' . $mob->getMonster()->getName() . '" place en ' . $mob->getCoordinates());

            return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
        }

        return $this->render('admin/map/mob_form.html.twig', [
            'map' => $map,
            'form' => $form->createView(),
            'title' => 'Placer un mob',
        ]);
    }

    #[Route('/{mapId}/mobs/{mobId}/edit', name: 'mob_edit', requirements: ['mapId' => '\d+', 'mobId' => '\d+'])]
    public function mobEdit(Request $request, int $mapId, int $mobId): Response
    {
        $map = $this->em->getRepository(Map::class)->find($mapId);
        $mob = $this->em->getRepository(Mob::class)->find($mobId);

        if (!$map || !$mob) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(MobSpawnType::class, $mob);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mob->setLife($mob->getMonster()->getLife());
            $mob->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->adminLogger->log('update', 'Mob', $mob->getId(), $mob->getMonster()->getName() . ' deplace en ' . $mob->getCoordinates());
            $this->addFlash('success', 'Mob "' . $mob->getMonster()->getName() . '" modifie.');

            return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
        }

        return $this->render('admin/map/mob_form.html.twig', [
            'map' => $map,
            'form' => $form->createView(),
            'title' => 'Modifier le mob',
            'mob' => $mob,
        ]);
    }

    #[Route('/{mapId}/mobs/{mobId}/delete', name: 'mob_delete', requirements: ['mapId' => '\d+', 'mobId' => '\d+'], methods: ['POST'])]
    public function mobDelete(Request $request, int $mapId, int $mobId): Response
    {
        $map = $this->em->getRepository(Map::class)->find($mapId);
        $mob = $this->em->getRepository(Mob::class)->find($mobId);

        if (!$map || !$mob) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_mob' . $mob->getId(), $request->request->get('_token'))) {
            $name = $mob->getMonster()->getName();
            $this->em->remove($mob);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Mob', null, $name . ' sur ' . $map->getName());
            $this->addFlash('success', 'Mob "' . $name . '" supprime.');
        }

        return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
    }

    // --- PNJ Position Management ---

    #[Route('/{mapId}/pnjs/{pnjId}/move', name: 'pnj_move', requirements: ['mapId' => '\d+', 'pnjId' => '\d+'])]
    public function pnjMove(Request $request, int $mapId, int $pnjId): Response
    {
        $map = $this->em->getRepository(Map::class)->find($mapId);
        $pnj = $this->em->getRepository(Pnj::class)->find($pnjId);

        if (!$map || !$pnj) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(PnjPositionType::class, $pnj, ['show_map_field' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->adminLogger->log('update', 'Pnj', $pnj->getId(), $pnj->getName() . ' deplace en ' . $pnj->getCoordinates());
            $this->addFlash('success', 'PNJ "' . $pnj->getName() . '" deplace en ' . $pnj->getCoordinates());

            return $this->redirectToRoute('admin_map_show', ['id' => $pnj->getMap()->getId()]);
        }

        return $this->render('admin/map/pnj_move.html.twig', [
            'map' => $map,
            'pnj' => $pnj,
            'form' => $form->createView(),
        ]);
    }

    // --- Portal Management ---

    #[Route('/{id}/portals/new', name: 'portal_new', requirements: ['id' => '\d+'])]
    public function portalNew(Request $request, Map $map): Response
    {
        $maps = $this->em->getRepository(Map::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $portal = new ObjectLayer();
            $portal->setName($request->request->get('name', 'Portal'));
            $portal->setSlug('portal-' . $request->request->get('coordinates', '0.0'));
            $portal->setType(ObjectLayer::TYPE_PORTAL);
            $portal->setCoordinates($request->request->get('coordinates', '0.0'));
            $portal->setMap($map);
            $portal->setUsable(true);
            $portal->setMovement(0);
            $portal->setItems(null);
            $portal->setActions(null);
            $portal->setCreatedAt(new \DateTime());
            $portal->setUpdatedAt(new \DateTime());

            $destMapId = $request->request->getInt('destination_map_id');
            if ($destMapId) {
                $portal->setDestinationMapId($destMapId);
            }
            $portal->setDestinationCoordinates($request->request->get('destination_coordinates', ''));

            $this->em->persist($portal);
            $this->em->flush();

            $this->adminLogger->log('create', 'Portal', $portal->getId(), $portal->getName() . ' sur ' . $map->getName());
            $this->addFlash('success', 'Portail "' . $portal->getName() . '" cree.');

            return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
        }

        return $this->render('admin/map/portal_form.html.twig', [
            'map' => $map,
            'maps' => $maps,
            'title' => 'Creer un portail',
        ]);
    }

    #[Route('/{mapId}/portals/{portalId}/edit', name: 'portal_edit', requirements: ['mapId' => '\d+', 'portalId' => '\d+'])]
    public function portalEdit(Request $request, int $mapId, int $portalId): Response
    {
        $map = $this->em->getRepository(Map::class)->find($mapId);
        $portal = $this->em->getRepository(ObjectLayer::class)->find($portalId);

        if (!$map || !$portal) {
            throw $this->createNotFoundException();
        }

        $maps = $this->em->getRepository(Map::class)->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $portal->setName($request->request->get('name', $portal->getName()));
            $portal->setCoordinates($request->request->get('coordinates', $portal->getCoordinates()));
            $portal->setSlug('portal-' . $portal->getCoordinates());
            $portal->setUpdatedAt(new \DateTime());

            $destMapId = $request->request->getInt('destination_map_id');
            if ($destMapId) {
                $portal->setDestinationMapId($destMapId);
            }
            $portal->setDestinationCoordinates($request->request->get('destination_coordinates', ''));

            $this->em->flush();

            $this->adminLogger->log('update', 'Portal', $portal->getId(), $portal->getName());
            $this->addFlash('success', 'Portail "' . $portal->getName() . '" modifie.');

            return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
        }

        return $this->render('admin/map/portal_form.html.twig', [
            'map' => $map,
            'maps' => $maps,
            'portal' => $portal,
            'title' => 'Modifier le portail',
        ]);
    }

    #[Route('/{mapId}/portals/{portalId}/delete', name: 'portal_delete', requirements: ['mapId' => '\d+', 'portalId' => '\d+'], methods: ['POST'])]
    public function portalDelete(Request $request, int $mapId, int $portalId): Response
    {
        $map = $this->em->getRepository(Map::class)->find($mapId);
        $portal = $this->em->getRepository(ObjectLayer::class)->find($portalId);

        if (!$map || !$portal) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete_portal' . $portal->getId(), $request->request->get('_token'))) {
            $name = $portal->getName();
            $this->em->remove($portal);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Portal', null, $name . ' sur ' . $map->getName());
            $this->addFlash('success', 'Portail "' . $name . '" supprime.');
        }

        return $this->redirectToRoute('admin_map_show', ['id' => $map->getId()]);
    }

    // --- TMX Import ---

    #[Route('/import', name: 'import')]
    #[IsGranted('ROLE_ADMIN')]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $file = $request->files->get('tmx_file');
            if (!$file || $file->getClientOriginalExtension() !== 'tmx') {
                $this->addFlash('error', 'Veuillez uploader un fichier .tmx valide.');

                return $this->redirectToRoute('admin_map_import');
            }

            $projectDir = $this->getParameter('kernel.project_dir');
            $terrainDir = $projectDir . '/terrain';

            $filename = $file->getClientOriginalName();
            $file->move($terrainDir, $filename);

            $syncEntities = $request->request->getBoolean('sync_entities');
            $options = '--all';
            if ($syncEntities) {
                $options .= ' --sync-entities';
            }

            $this->adminLogger->log('import', 'Map', null, 'TMX import: ' . $filename);
            $this->addFlash('success', 'Fichier "' . $filename . '" uploade dans terrain/. Executez la commande app:terrain:import pour l\'importer.');

            return $this->redirectToRoute('admin_map_index');
        }

        return $this->render('admin/map/import.html.twig');
    }
}
