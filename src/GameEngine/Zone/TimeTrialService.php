<?php

namespace App\GameEngine\Zone;

use App\Entity\App\Player;
use App\Entity\App\TimeTrial;
use App\Entity\App\TimeTrialRun;
use App\Event\Zone\PlayerTraveledEvent;
use App\Repository\TimeTrialRunRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Parcours chronometres asynchrones (tache 133).
 *
 * Le pivot PBBG a rendu impossible la « course entre joueurs » d'origine : sans
 * deplacement en tuiles, il n'y a plus rien a courir en direct. Ce qui en
 * faisait l'interet — comparer des trajets — se conserve en asynchrone : chacun
 * rallie la meme suite de zones quand il veut, et seuls les temps se comparent.
 *
 * Le chrono n'est pas une epreuve de reflexes. Il recompense la **preparation**
 * (une monture raccourcit chaque liaison, cf. tache 130), la connaissance du
 * graphe — plusieurs routes, de longueurs differentes — et la gestion de
 * l'energie.
 */
class TimeTrialService implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TimeTrialRunRepository $runRepository,
        private readonly ActionEnergyManager $energyManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PlayerTraveledEvent::NAME => 'onPlayerTraveled',
        ];
    }

    /**
     * Lance une tentative.
     *
     * @throws ZoneActionException si le parcours est refuse (cle de traduction en message)
     */
    public function start(Player $player, TimeTrial $trial): TimeTrialRun
    {
        if (!$trial->isEnabled()) {
            throw new ZoneActionException('game.time_trial.error.disabled');
        }
        if (0 === $trial->countCheckpoints()) {
            throw new ZoneActionException('game.time_trial.error.no_checkpoint');
        }
        if ($player->isTraveling()) {
            throw new ZoneActionException('game.time_trial.error.traveling');
        }
        if ($player->getCurrentZone()?->getSlug() !== $trial->getStartZone()->getSlug()) {
            throw new ZoneActionException('game.time_trial.error.wrong_zone');
        }

        $current = $this->settleRunning($player);
        if (null !== $current) {
            throw new ZoneActionException('game.time_trial.error.already_running');
        }

        // L'energie part avant la creation : un depart qu'on ne peut pas payer
        // ne doit pas laisser de tentative ouverte derriere lui.
        $this->energyManager->spend($player, $trial->getEnergyCost(), false);

        $run = new TimeTrialRun($player, $trial);
        $this->entityManager->persist($run);
        $this->entityManager->flush();

        $this->logger->info('Time trial started', [
            'player_id' => $player->getId(),
            'trial' => $trial->getSlug(),
        ]);

        return $run;
    }

    /**
     * Avance la tentative en cours si la zone atteinte est l'etape attendue.
     *
     * Traverser une zone qui n'est pas la prochaine etape est **sans effet** :
     * le parcours impose un ordre, pas un itineraire. C'est precisement ce qui
     * laisse au joueur le choix de sa route.
     */
    public function onPlayerTraveled(PlayerTraveledEvent $event): void
    {
        $run = $this->settleRunning($event->getPlayer());
        if (null === $run) {
            return;
        }

        if ($run->nextCheckpoint() !== $event->getZone()->getSlug()) {
            return;
        }

        $run->advance();

        if (null === $run->nextCheckpoint()) {
            $run->finish(new \DateTimeImmutable());

            $this->logger->info('Time trial finished', [
                'player_id' => $event->getPlayer()->getId(),
                'trial' => $run->getTrial()->getSlug(),
                'elapsed' => $run->getElapsedSeconds(),
            ]);
        }

        $this->entityManager->flush();
    }

    /**
     * Tentative en cours du joueur, apres constat d'un eventuel depassement.
     *
     * Le depassement est constate paresseusement, comme l'arrivee de voyage :
     * une tentative oubliee ne doit pas exiger un cron pour liberer le joueur.
     */
    public function settleRunning(Player $player): ?TimeTrialRun
    {
        $run = $this->runRepository->findRunning($player);
        if (null === $run) {
            return null;
        }

        $now = new \DateTimeImmutable();
        if (!$run->hasExceededLimit($now)) {
            return $run;
        }

        $run->expire($now);
        $this->entityManager->flush();

        return null;
    }

    public function abandon(Player $player): void
    {
        $run = $this->settleRunning($player);
        if (null === $run) {
            throw new ZoneActionException('game.time_trial.error.not_running');
        }

        $run->abandon(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
