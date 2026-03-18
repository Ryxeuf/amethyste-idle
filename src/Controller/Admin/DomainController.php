<?php

namespace App\Controller\Admin;

use App\Entity\Game\Domain;
use App\Form\Admin\DomainType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/domains', name: 'admin_domain_')]
#[IsGranted('ROLE_GAME_DESIGNER')]
class DomainController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminLogger $adminLogger,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $search = $request->query->get('q', '');
        $qb = $this->em->getRepository(Domain::class)->createQueryBuilder('d');

        if ($search) {
            $qb->where('LOWER(d.title) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('d.title', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(d.id)')->getQuery()->getSingleScalarResult();
        $domains = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/domain/index.html.twig', [
            'domains' => $domains,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $domain = new Domain();
        $form = $this->createForm(DomainType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($domain);
            $this->em->flush();
            $this->adminLogger->log('create', 'Domain', $domain->getId(), $domain->getTitle());
            $this->addFlash('success', 'Domaine "' . $domain->getTitle() . '" cree avec succes.');

            return $this->redirectToRoute('admin_domain_index');
        }

        return $this->render('admin/domain/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, Domain $domain): Response
    {
        $form = $this->createForm(DomainType::class, $domain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->adminLogger->log('update', 'Domain', $domain->getId(), $domain->getTitle());
            $this->addFlash('success', 'Domaine "' . $domain->getTitle() . '" modifie avec succes.');

            return $this->redirectToRoute('admin_domain_index');
        }

        return $this->render('admin/domain/edit.html.twig', [
            'domain' => $domain,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Domain $domain): Response
    {
        if ($this->isCsrfTokenValid('delete' . $domain->getId(), $request->request->get('_token'))) {
            $title = $domain->getTitle();
            $this->em->remove($domain);
            $this->em->flush();
            $this->adminLogger->log('delete', 'Domain', null, $title);
            $this->addFlash('success', 'Domaine supprime avec succes.');
        }

        return $this->redirectToRoute('admin_domain_index');
    }
}
