<?php

namespace App\GameEngine\Progression;

use App\Entity\App\Player;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\Zone;
use App\GameEngine\Renown\PlayerRenownManager;
use App\Repository\PlayerZoneActivityRepository;
use App\Repository\ZoneRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Le foyer d'attache, constate a la cloture de l'acte I (ONB-13).
 *
 * GAME_ONBOARDING § 4.4, amendement a GAME_WORLD § 13.1 : **il ne se choisit
 * pas, il se gagne**. Le canon derivait le foyer de la race — ce qui revenait a
 * demander une orientation de carriere avant toute experience de jeu, et
 * poussait ailleurs l'Elfe qui voulait miner. Ici, c'est la zone ou le joueur a
 * reellement travaille pendant l'acte I.
 *
 * **Ce que le foyer apporte, et rien d'autre** : une lettre au journal, un cran
 * de renommee, une premiere destination suggeree. **Aucun contenu ouvert,
 * aucun contenu ferme, aucun bonus de rendement.** Il enregistre une
 * orientation deja prise ; il ne l'oriente pas — et
 * `HomeSettlementTest::testTheHomeIsNeverReadAsAGate()` refuse qu'il le
 * devienne.
 *
 * **Sans activite distinctive, le Fanal.** Le canon le reservait a l'Humain ;
 * le cas humain devient le cas par defaut de tout le monde. Un joueur qui a
 * traverse l'acte I sans jamais recolter ailleurs n'a rien decide, et le lui
 * faire decider retroactivement serait pire que de ne rien dire.
 */
class HomeSettlementResolver
{
    /**
     * Le Fanal — defaut quand rien ne distingue.
     */
    public const DEFAULT_HOME_SLUG = 'village-de-lumiere';

    /**
     * Le cran de renommee accorde a la cloture.
     *
     * Un cran, pas une avance : le foyer marque une orientation, il ne prend
     * pas les devants sur ce que le joueur n'a pas encore fait.
     */
    public const RENOWN_NOTCH = 25;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerZoneActivityRepository $activityRepository,
        private readonly ZoneRepository $zoneRepository,
        private readonly PlayerRenownManager $renownManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Constate le foyer et le remet au joueur. Idempotent.
     *
     * L'idempotence n'est pas une precaution de style : l'arc `intro` est
     * **rejouable par personnage**, et un second passage ne doit pas redonner
     * un cran de renommee ni reecrire une lettre deja lue.
     */
    public function claim(Player $player): ?Zone
    {
        if ($player->hasClaimedHomeZone()) {
            return $player->getHomeZone();
        }

        $home = $this->busiestZone($player) ?? $this->defaultHome();
        if (null === $home) {
            return null;
        }

        $player->claimHomeZone($home);

        $this->renownManager->addRenown($player, self::RENOWN_NOTCH, 'home_settlement');
        $this->writeLetter($player, $home);

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $home;
    }

    /**
     * La zone ou le joueur a le plus travaille, ou `null` s'il n'a rien fait.
     */
    public function busiestZone(Player $player): ?Zone
    {
        $activities = $this->activityRepository->findBusiestFor($player);

        return $activities === [] ? null : $activities[0]->getZone();
    }

    private function defaultHome(): ?Zone
    {
        return $this->zoneRepository->findOneBy(['slug' => self::DEFAULT_HOME_SLUG]);
    }

    /**
     * La lettre — c'est le foyer qui ecrit, pas le jeu qui felicite.
     */
    private function writeLetter(Player $player, Zone $home): void
    {
        $entry = new PlayerJournalEntry();
        $entry->setPlayer($player);
        $entry->setType(PlayerJournalEntry::TYPE_EXPLORATION);
        $entry->setMessage($this->translator->trans('game.onboarding.home.letter', ['%zone%' => $home->getName()]));
        $entry->setMetadata([
            'zone' => $home->getSlug(),
            'action' => 'home_settlement',
        ]);

        $this->entityManager->persist($entry);
    }
}
