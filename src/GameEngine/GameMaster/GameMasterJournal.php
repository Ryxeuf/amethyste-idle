<?php

namespace App\GameEngine\GameMaster;

use App\Entity\App\Player;
use App\Service\AdminLogger;

/**
 * Trace des gestes d'animation, dans le journal d'administration.
 *
 * `AdminLogger` couvre deja ce qui se fait *depuis* l'admin. Ce qu'un MJ fait
 * *depuis le jeu* — lancer un evenement, faire apparaitre un monstre, annoncer,
 * passer incognito — n'y laissait rien. Le jour ou un joueur conteste une
 * animation, « je ne sais pas » n'est pas une reponse acceptable.
 *
 * Meme table, meme ecran : les entrees sont marquees `entityType = GameMaster`,
 * ce qui suffit a les isoler sans dupliquer un journal.
 */
class GameMasterJournal
{
    public const ENTITY_TYPE = 'GameMaster';

    public function __construct(
        private readonly AdminLogger $adminLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function record(Player $gameMaster, string $action, string $summary, array $details = []): void
    {
        $this->adminLogger->log(
            'gm_' . $action,
            self::ENTITY_TYPE,
            $gameMaster->getId(),
            sprintf('%s — %s', $gameMaster->getName(), $summary),
            $details + ['game_master' => $gameMaster->getName()],
        );
    }
}
