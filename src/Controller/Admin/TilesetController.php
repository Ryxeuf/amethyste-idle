<?php

namespace App\Controller\Admin;

use App\Entity\App\Tileset;
use App\GameEngine\Terrain\TilesetRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tilesets', name: 'admin_tileset_')]
#[IsGranted('ROLE_WORLD_BUILDER')]
class TilesetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TilesetRegistry $tilesetRegistry,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $tilesets = $this->em->getRepository(Tileset::class)->findBy([], ['firstGid' => 'ASC']);

        return $this->render('admin/tileset/index.html.twig', [
            'tilesets' => $tilesets,
        ]);
    }

    /**
     * Scanner les images disponibles dans assets/styles/images/ pour les proposer comme tilesets.
     */
    #[Route('/browse-images', name: 'browse_images', methods: ['GET'])]
    public function browseImages(Request $request): JsonResponse
    {
        $assetsDir = $this->getParameter('kernel.project_dir') . '/assets/styles/images';

        if (!is_dir($assetsDir)) {
            return $this->json(['images' => []]);
        }

        $finder = new Finder();
        $finder->files()
            ->in($assetsDir)
            ->name('*.png')
            ->sortByName();

        $images = [];
        foreach ($finder as $file) {
            $relativePath = str_replace($assetsDir . '/', '', $file->getRealPath());

            // Recuperer les dimensions de l'image
            $size = @getimagesize($file->getRealPath());
            if (!$size) {
                continue;
            }

            $images[] = [
                'path' => $relativePath,
                'filename' => $file->getFilename(),
                'directory' => dirname($relativePath),
                'width' => $size[0],
                'height' => $size[1],
                'sizeKb' => round($file->getSize() / 1024),
            ];
        }

        return $this->json(['images' => $images]);
    }

    /**
     * Previsualiser une image (retourne l'URL publique via AssetMapper).
     */
    #[Route('/preview-image', name: 'preview_image', methods: ['GET'])]
    public function previewImage(Request $request): JsonResponse
    {
        $path = $request->query->get('path', '');

        if (!$path) {
            return $this->json(['error' => 'Chemin manquant'], 400);
        }

        $fullPath = $this->getParameter('kernel.project_dir') . '/assets/styles/images/' . $path;

        if (!file_exists($fullPath)) {
            return $this->json(['error' => 'Image introuvable'], 404);
        }

        $size = @getimagesize($fullPath);
        if (!$size) {
            return $this->json(['error' => 'Fichier non valide'], 400);
        }

        return $this->json([
            'path' => $path,
            'width' => $size[0],
            'height' => $size[1],
            'suggestedColumns32' => (int) floor($size[0] / 32),
            'suggestedRows32' => (int) floor($size[1] / 32),
            'suggestedTileCount32' => (int) (floor($size[0] / 32) * floor($size[1] / 32)),
        ]);
    }

    /**
     * Enregistrer un nouveau tileset custom.
     */
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $name = trim((string) $request->request->get('name', ''));
        $imagePath = trim((string) $request->request->get('image_path', ''));
        $columns = (int) $request->request->get('columns', 0);
        $tileWidth = (int) $request->request->get('tile_width', 32);
        $tileHeight = (int) $request->request->get('tile_height', 32);

        if (!$name || !$imagePath || $columns <= 0) {
            $this->addFlash('error', 'Parametres invalides: nom, chemin image et colonnes sont requis.');

            return $this->redirectToRoute('admin_tileset_index');
        }

        // Verifier que le nom n'existe pas deja
        $existing = $this->em->getRepository(Tileset::class)->findOneBy(['name' => $name]);
        if ($existing) {
            $this->addFlash('error', sprintf('Un tileset "%s" existe deja.', $name));

            return $this->redirectToRoute('admin_tileset_index');
        }

        // Verifier que l'image existe
        $fullPath = $this->getParameter('kernel.project_dir') . '/assets/styles/images/' . $imagePath;
        if (!file_exists($fullPath)) {
            $this->addFlash('error', sprintf('Image introuvable: %s', $imagePath));

            return $this->redirectToRoute('admin_tileset_index');
        }

        // Calculer le nombre de tiles depuis les dimensions de l'image
        $size = @getimagesize($fullPath);
        if (!$size) {
            $this->addFlash('error', 'Impossible de lire les dimensions de l\'image.');

            return $this->redirectToRoute('admin_tileset_index');
        }

        $rows = (int) floor($size[1] / $tileHeight);
        $tileCount = $columns * $rows;

        // Calculer le firstGid automatiquement
        $firstGid = $this->tilesetRegistry->getNextAvailableFirstGid();

        $tileset = new Tileset();
        $tileset->setName($name);
        $tileset->setImagePath($imagePath);
        $tileset->setColumnsCount($columns);
        $tileset->setTileCount($tileCount);
        $tileset->setTileWidth($tileWidth);
        $tileset->setTileHeight($tileHeight);
        $tileset->setFirstGid($firstGid);
        $tileset->setIsBuiltin(false);
        $tileset->setIsEditable(true);

        $this->em->persist($tileset);
        $this->em->flush();

        $this->tilesetRegistry->clearCache();

        $this->addFlash('success', sprintf(
            'Tileset "%s" cree avec %d tiles (firstGid: %d).',
            $name,
            $tileCount,
            $firstGid
        ));

        return $this->redirectToRoute('admin_tileset_index');
    }

    /**
     * Supprimer un tileset custom (les built-in ne sont pas supprimables).
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Tileset $tileset): Response
    {
        if ($tileset->isBuiltin()) {
            $this->addFlash('error', 'Les tilesets built-in ne peuvent pas etre supprimes.');

            return $this->redirectToRoute('admin_tileset_index');
        }

        $name = $tileset->getName();
        $this->em->remove($tileset);
        $this->em->flush();

        $this->tilesetRegistry->clearCache();

        $this->addFlash('success', sprintf('Tileset "%s" supprime.', $name));

        return $this->redirectToRoute('admin_tileset_index');
    }
}
