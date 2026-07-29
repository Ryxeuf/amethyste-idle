<?php

namespace App\Twig;

use App\Helper\PlayerHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Le statut MJ du personnage connecte, pour les gabarits.
 *
 * La navigation ne peut pas se decider sur `is_granted('ROLE_...')` : le statut
 * est porte par le **personnage**, pas par le compte. Un membre du staff
 * connecte sur son perso ordinaire ne doit pas voir la console.
 *
 * Le personnage est resolu une fois par requete et memorise : la barre de
 * navigation pose la question deux fois (bureau et mobile), et la reponse ne
 * change pas entre les deux.
 */
class GameMasterExtension extends AbstractExtension
{
    private ?bool $cache = null;

    public function __construct(
        private readonly PlayerHelper $playerHelper,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_game_master', $this->isGameMaster(...)),
        ];
    }

    public function isGameMaster(): bool
    {
        return $this->cache ??= $this->playerHelper->getPlayer()?->isGameMaster() ?? false;
    }
}
