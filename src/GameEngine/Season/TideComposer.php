<?php

namespace App\GameEngine\Season;

use App\Entity\App\GameEvent;
use App\Entity\App\InfluenceSeason;
use App\Repository\GameEventRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Pose l'arc d'une maree sur la saison qui vient (FOY-15, elargi par NAR-15).
 *
 * Les marees ecrites de l'an 1 ont leurs beats en fixtures (`SeasonArcFixtures`,
 * NAR-08) : on sait d'avance qu'elles arriveront. Une consequence, par
 * definition, non — son arc doit donc se composer au moment ou le monde la
 * declenche, a partir de la meme donnee declarative. **Une rotation est dans le
 * meme cas** : le gabarit tire depend de ce qui manque au monde le jour du
 * tirage.
 *
 * Les deux voix composables partagent donc **un seul chemin**, et le composeur
 * ne sait pas laquelle il sert : il prend une **clef de maree**, la demande au
 * chargeur, et pose ce qu'on lui rend. Lui faire distinguer les voix aurait
 * duplique la pose des beats — *une regle recopiee derive de son original en
 * silence*.
 *
 * **Les fenetres sont derivees des bornes reelles de la saison**, exactement
 * comme en fixtures. Les partir d'une date fixe aurait desynchronise l'arc de
 * la maree des que le calendrier aurait glisse d'un jour.
 *
 * **Idempotent.** Un tick rejoue ne doit pas empiler un second arc sur la meme
 * saison : une saison qui porte deja des beats est laissee telle quelle. Sans
 * cette garde, un tick relance a la main aurait double les quatre beats, et
 * `getActiveBeat()` aurait rendu le premier des deux — un bug invisible.
 */
class TideComposer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameEventRepository $gameEventRepository,
        private readonly TideDefinitionLoader $loader,
    ) {
    }

    /**
     * Compose l'arc, et rend le nombre de beats poses (zero si deja fait).
     *
     * @param string $tide clef de maree — une consequence ou un gabarit de rotation
     *
     * @throws \InvalidArgumentException si la clef ne designe aucune maree composable
     */
    public function compose(InfluenceSeason $season, string $tide): int
    {
        if ([] !== $this->gameEventRepository->findBySeasonOrdered($season)) {
            return 0;
        }

        $definition = $this->loader->composable($tide);

        // Une clef inconnue poserait un theme sans arc : la maree s'afficherait
        // et rien ne s'y passerait. On refuse plutot que de composer a moitie.
        if ($definition === null) {
            throw new \InvalidArgumentException(sprintf('Aucune marée composable pour la clef « %s ».', $tide));
        }
        $base = \DateTime::createFromInterface($season->getStartsAt());

        $season->setTheme($definition['theme']);

        foreach ($definition['beats'] as $beat) {
            $event = new GameEvent();
            $event->setName($beat['name']);
            $event->setDescription($beat['description']);
            $event->setType(GameEvent::TYPE_CUSTOM);
            $event->setStatus(GameEvent::STATUS_SCHEDULED);
            $event->setStartsAt((clone $base)->modify(sprintf('+%d days', $beat['start_day'])));
            $event->setEndsAt((clone $base)->modify(sprintf('+%d days', $beat['end_day'])));
            $event->setSeason($season);
            $event->setBeat($beat['beat']);
            $event->setBeatOrder($beat['order']);
            $event->setCreatedAt(new \DateTime());
            $event->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($event);
        }

        $this->entityManager->flush();

        return \count($definition['beats']);
    }
}
