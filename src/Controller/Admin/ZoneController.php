<?php

namespace App\Controller\Admin;

use App\Entity\App\Mob;
use App\Entity\App\ObjectLayer;
use App\Entity\App\Player;
use App\Entity\App\Pnj;
use App\Entity\App\Zone;
use App\Entity\App\ZoneConnection;
use App\Entity\App\ZoneVein;
use App\Form\Admin\ZoneType;
use App\GameEngine\Zone\ZoneDefinitionException;
use App\GameEngine\Zone\ZoneDefinitionLoader;
use App\GameEngine\Zone\ZoneImporter;
use App\Repository\ZoneConnectionRepository;
use App\Repository\ZoneRepository;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Pilotage des zones du monde (pivot PBBG).
 *
 * Depuis la suppression du code carte (ZON-21), la position d'un joueur est sa
 * zone — mais aucun ecran d'administration ne permettait de voir ni de corriger
 * ce graphe : il fallait editer le YAML puis relancer un import en ligne de
 * commande, sans jamais voir l'etat reel de la base.
 *
 * Le YAML (`config/game/zones/*.yaml`) reste la source de verite pour le
 * contenu livre ; ces ecrans montrent et corrigent l'etat en base. Une
 * modification ici est ecrasee au prochain `app:zone:import` si le YAML dit
 * autre chose — l'ecran le rappelle.
 */
#[Route('/admin/zones', name: 'admin_zone_')]
#[IsGranted('ROLE_WORLD_BUILDER')]
class ZoneController extends AbstractController
{
    private const PER_PAGE = 25;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ZoneRepository $zoneRepository,
        private readonly ZoneConnectionRepository $zoneConnectionRepository,
        private readonly ZoneDefinitionLoader $definitionLoader,
        private readonly ZoneImporter $zoneImporter,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', '');
        $state = (string) $request->query->get('state', '');

        $qb = $this->zoneRepository->createQueryBuilder('z');

        if ('' !== $search) {
            $qb->andWhere('LOWER(z.name) LIKE :q OR LOWER(z.slug) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }
        if (\in_array($type, Zone::getTypes(), true)) {
            $qb->andWhere('z.type = :type')->setParameter('type', $type);
        }
        if ('enabled' === $state || 'disabled' === $state) {
            $qb->andWhere('z.enabled = :enabled')->setParameter('enabled', 'enabled' === $state);
        }

        $total = (int) (clone $qb)->select('COUNT(z.id)')->getQuery()->getSingleScalarResult();
        $page = max(1, $request->query->getInt('page', 1));

        /** @var list<Zone> $zones */
        $zones = $qb->orderBy('z.name', 'ASC')
            ->setFirstResult(($page - 1) * self::PER_PAGE)
            ->setMaxResults(self::PER_PAGE)
            ->getQuery()
            ->getResult();

        return $this->render('admin/zone/index.html.twig', [
            'zones' => $zones,
            'counts' => $this->countsFor($zones),
            'search' => $search,
            'type' => $type,
            'state' => $state,
            'types' => Zone::getTypes(),
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'orphanPlayers' => $this->countPlayersWithoutZone(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $zone = new Zone();
        $form = $this->createForm(ZoneType::class, $zone, ['allow_slug_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyTranslations($zone, $form->get('nameEn')->getData(), $form->get('descriptionEn')->getData());

            $this->em->persist($zone);
            $this->em->flush();

            $this->adminLogger->log('create', 'Zone', $zone->getId(), $zone->getName());
            $this->addFlash('success', sprintf('Zone "%s" creee.', $zone->getName()));

            return $this->redirectToRoute('admin_zone_show', ['id' => $zone->getId()]);
        }

        return $this->render('admin/zone/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Rejoue l'import declaratif depuis le YAML, sans passer par la console.
     *
     * L'import est idempotent et non destructif (cf. `ZoneImporter`) : c'est ce
     * qui permet de l'offrir dans une interface. Le mode verification (dry-run)
     * donne le rapport sans rien ecrire.
     */
    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('zone_import', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_zone_index');
        }

        $dryRun = '1' === $request->request->get('dry_run');

        try {
            $definition = $this->definitionLoader->loadFile($this->definitionLoader->defaultFile());
            $report = $this->zoneImporter->import($definition, $dryRun);
        } catch (ZoneDefinitionException $e) {
            $this->addFlash('error', 'Definition YAML invalide : ' . $e->getMessage());

            return $this->redirectToRoute('admin_zone_index');
        }

        $summary = sprintf(
            'zones %d creees / %d mises a jour, liaisons %d creees / %d mises a jour, creatures %d, PNJ %d crees / %d mis a jour',
            $report->zonesCreated,
            $report->zonesUpdated,
            $report->connectionsCreated,
            $report->connectionsUpdated,
            $report->mobsCreated,
            $report->pnjsCreated,
            $report->pnjsUpdated,
        );

        foreach ($report->warnings as $warning) {
            $this->addFlash('warning', $warning);
        }

        if ($dryRun) {
            $this->addFlash('info', 'Verification (aucune ecriture) : ' . $summary);
        } else {
            $this->adminLogger->log('zone_import', 'Zone', null, $summary);
            $this->addFlash('success', 'Import applique : ' . $summary);
        }

        return $this->redirectToRoute('admin_zone_index');
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Zone $zone): Response
    {
        $mobs = $this->em->getRepository(Mob::class)->createQueryBuilder('m')
            ->leftJoin('m.monster', 'mo')->addSelect('mo')
            ->andWhere('m.zone = :zone')->setParameter('zone', $zone)
            ->orderBy('mo.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Une population se lit par espece, pas creature par creature : c'est le
        // compte declare en YAML qu'un world builder compare a la realite.
        $population = [];
        foreach ($mobs as $mob) {
            $monster = $mob->getMonster();
            $key = $monster->getSlug();
            $population[$key] ??= ['monster' => $monster, 'total' => 0, 'alive' => 0, 'nocturnal' => 0];
            ++$population[$key]['total'];
            // `isDead()` couvre aussi le respawn en attente (diedAt) : compter
            // seulement les PV laisserait passer une creature en file de respawn.
            if (!$mob->isDead()) {
                ++$population[$key]['alive'];
            }
            if ($mob->isNocturnal()) {
                ++$population[$key]['nocturnal'];
            }
        }
        ksort($population);

        $veins = $this->em->getRepository(ZoneVein::class)->findBy(['zone' => $zone], ['slug' => 'ASC']);
        $veinBySlug = [];
        foreach ($veins as $vein) {
            $veinBySlug[$vein->getSlug()] = $vein;
        }

        return $this->render('admin/zone/show.html.twig', [
            'zone' => $zone,
            'outgoing' => $this->zoneConnectionRepository->findBy(['fromZone' => $zone], ['id' => 'ASC']),
            'incoming' => $this->zoneConnectionRepository->findBy(['toZone' => $zone], ['id' => 'ASC']),
            'population' => array_values($population),
            'pnjs' => $this->em->getRepository(Pnj::class)->findBy(['zone' => $zone], ['name' => 'ASC']),
            'pois' => $this->em->getRepository(ObjectLayer::class)->findBy(['zone' => $zone], ['type' => 'ASC']),
            'playersPresent' => $this->em->getRepository(Player::class)->findBy(['currentZone' => $zone], ['name' => 'ASC'], 50),
            'gatherResources' => $zone->getGatherResources(),
            'veins' => $veinBySlug,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Zone $zone): Response
    {
        $form = $this->createForm(ZoneType::class, $zone);
        $form->get('nameEn')->setData($zone->getNameTranslations()['en'] ?? null);
        $form->get('descriptionEn')->setData($zone->getDescriptionTranslations()['en'] ?? null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyTranslations($zone, $form->get('nameEn')->getData(), $form->get('descriptionEn')->getData());
            $this->em->flush();

            $this->adminLogger->log('update', 'Zone', $zone->getId(), $zone->getName());
            $this->addFlash('success', sprintf('Zone "%s" mise a jour.', $zone->getName()));

            return $this->redirectToRoute('admin_zone_show', ['id' => $zone->getId()]);
        }

        return $this->render('admin/zone/edit.html.twig', [
            'zone' => $zone,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(Request $request, Zone $zone): Response
    {
        if (!$this->isCsrfTokenValid('zone_toggle' . $zone->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('admin_zone_show', ['id' => $zone->getId()]);
        }

        $zone->setEnabled(!$zone->isEnabled());
        $this->em->flush();

        $this->adminLogger->log($zone->isEnabled() ? 'enable' : 'disable', 'Zone', $zone->getId(), $zone->getName());
        $this->addFlash('success', sprintf('Zone "%s" %s.', $zone->getName(), $zone->isEnabled() ? 'activee' : 'desactivee'));

        return $this->redirectToRoute('admin_zone_show', ['id' => $zone->getId()]);
    }

    /**
     * Compteurs affiches dans la liste, en une requete par famille.
     *
     * Compter dans la boucle du gabarit ferait une requete par zone et par
     * famille — le prix d'un ecran d'administration ne doit pas dependre du
     * nombre de zones.
     *
     * @param list<Zone> $zones
     *
     * @return array<int, array{connections: int, mobs: int, pnjs: int, players: int, pois: int}>
     */
    private function countsFor(array $zones): array
    {
        $counts = [];
        foreach ($zones as $zone) {
            $counts[$zone->getId()] = ['connections' => 0, 'mobs' => 0, 'pnjs' => 0, 'players' => 0, 'pois' => 0];
        }
        if ([] === $counts) {
            return $counts;
        }

        $ids = array_keys($counts);
        $queries = [
            'connections' => [ZoneConnection::class, 'fromZone'],
            'mobs' => [Mob::class, 'zone'],
            'pnjs' => [Pnj::class, 'zone'],
            'players' => [Player::class, 'currentZone'],
            'pois' => [ObjectLayer::class, 'zone'],
        ];

        foreach ($queries as $key => [$entity, $field]) {
            $rows = $this->em->createQuery(sprintf(
                'SELECT z.id AS zoneId, COUNT(e.id) AS total FROM %s e JOIN e.%s z WHERE z.id IN (:ids) GROUP BY z.id',
                $entity,
                $field,
            ))->setParameter('ids', $ids)->getResult();

            foreach ($rows as $row) {
                $counts[(int) $row['zoneId']][$key] = (int) $row['total'];
            }
        }

        return $counts;
    }

    private function countPlayersWithoutZone(): int
    {
        return (int) $this->em->createQuery(
            sprintf('SELECT COUNT(p.id) FROM %s p WHERE p.currentZone IS NULL', Player::class)
        )->getSingleScalarResult();
    }

    private function applyTranslations(Zone $zone, ?string $nameEn, ?string $descriptionEn): void
    {
        $zone->setNameTranslations(null !== $nameEn && '' !== trim($nameEn) ? ['en' => $nameEn] : null);
        $zone->setDescriptionTranslations(null !== $descriptionEn && '' !== trim($descriptionEn) ? ['en' => $descriptionEn] : null);
    }
}
