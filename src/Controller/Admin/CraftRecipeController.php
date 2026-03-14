<?php

namespace App\Controller\Admin;

use App\Entity\Game\CraftRecipe;
use App\Form\Admin\CraftRecipeType;
use App\Service\AdminLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/craft-recipes', name: 'admin_craft_recipe_')]
#[IsGranted('ROLE_ADMIN')]
class CraftRecipeController extends AbstractController
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
        $qb = $this->em->getRepository(CraftRecipe::class)->createQueryBuilder('r');

        if ($search) {
            $qb->where('LOWER(r.name) LIKE LOWER(:q) OR LOWER(r.profession) LIKE LOWER(:q)')
               ->setParameter('q', '%' . $search . '%');
        }

        $qb->orderBy('r.profession', 'ASC')->addOrderBy('r.name', 'ASC');

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 25;
        $total = (int) (clone $qb)->select('COUNT(r.id)')->getQuery()->getSingleScalarResult();
        $recipes = $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/craft_recipe/index.html.twig', [
            'recipes' => $recipes,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => max(1, (int) ceil($total / $limit)),
            'total' => $total,
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        $recipe = new CraftRecipe();
        $form = $this->createForm(CraftRecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredientsJson = $form->get('ingredientsJson')->getData();
            if ($ingredientsJson) {
                $recipe->setIngredients(json_decode($ingredientsJson, true) ?: []);
            }

            $this->em->persist($recipe);
            $this->em->flush();
            $this->adminLogger->log('create', 'CraftRecipe', $recipe->getId(), $recipe->getName());
            $this->addFlash('success', 'Recette "' . $recipe->getName() . '" creee avec succes.');

            return $this->redirectToRoute('admin_craft_recipe_index');
        }

        return $this->render('admin/craft_recipe/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Request $request, CraftRecipe $recipe): Response
    {
        $form = $this->createForm(CraftRecipeType::class, $recipe);

        if (!$form->isSubmitted()) {
            $form->get('ingredientsJson')->setData(json_encode($recipe->getIngredients(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ingredientsJson = $form->get('ingredientsJson')->getData();
            if ($ingredientsJson) {
                $recipe->setIngredients(json_decode($ingredientsJson, true) ?: []);
            }

            $this->em->flush();
            $this->adminLogger->log('update', 'CraftRecipe', $recipe->getId(), $recipe->getName());
            $this->addFlash('success', 'Recette "' . $recipe->getName() . '" modifiee avec succes.');

            return $this->redirectToRoute('admin_craft_recipe_index');
        }

        return $this->render('admin/craft_recipe/edit.html.twig', [
            'recipe' => $recipe,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, CraftRecipe $recipe): Response
    {
        if ($this->isCsrfTokenValid('delete' . $recipe->getId(), $request->request->get('_token'))) {
            $name = $recipe->getName();
            $this->em->remove($recipe);
            $this->em->flush();
            $this->adminLogger->log('delete', 'CraftRecipe', null, $name);
            $this->addFlash('success', 'Recette supprimee avec succes.');
        }

        return $this->redirectToRoute('admin_craft_recipe_index');
    }
}
