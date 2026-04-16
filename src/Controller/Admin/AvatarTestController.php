<?php

namespace App\Controller\Admin;

use App\GameEngine\Map\SpriteConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/avatar-test', name: 'admin_avatar_test_')]
class AvatarTestController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(SpriteConfigProvider $spriteConfigProvider): Response
    {
        $singleSprite = $spriteConfigProvider->getPlayerSprites()['player_default'] ?? null;
        $multiSprite = $spriteConfigProvider->getMobSprites()['mob_zombie'] ?? null;

        return $this->render('admin/avatar_test/index.html.twig', [
            'singleSprite' => $singleSprite,
            'multiSprite' => $multiSprite,
        ]);
    }
}
