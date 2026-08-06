<?php

namespace App\GameEngine\Progression;

use App\Entity\App\DomainExperience;
use App\Entity\App\Player;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\DomainLevelUpEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Le canal qui manquait : le combat rapporte enfin des points (ARC-06b).
 *
 * ARC-06a avait pose la table du gain (`DomainPointYield`) en supposant qu'il
 * suffisait d'indexer un gain existant. Mesure le 2026-08-05 : **il n'en
 * existait aucun** — seule la materia gagnait de l'experience
 * (`MateriaXpGranter`), et un arbre de combat ne pouvait litteralement pas se
 * monter en combattant. C'est cette classe, et elle seule, qui referme
 * l'ecart.
 *
 * Elle suit `MateriaXpGranter` de pres, parce que les deux repondent au meme
 * evenement et doivent repondre aux memes abus :
 *
 *  - **les invocations ne rapportent rien** — sinon un invocateur ferait
 *    tourner sa propre monnaie (`Mob::isSummoned()`) ;
 *  - **le coop partage** au lieu de multiplier, avec le meme plancher de 1 :
 *    quatre joueurs sur un T1 valent un quart chacun et non zero, la table
 *    descendant deja a la plus petite unite qu'elle nomme ;
 *  - **un joueur mort ne recoit rien** : il n'a pas fini la rencontre.
 *
 * Ce qu'elle ajoute, et qui n'a pas d'equivalent cote materia : le gain va a
 * **une** case, celle du geste joue (`CombatGestureLedger`), et il passe par
 * le reste en quarts (`DomainExperience::addQuarters()`), sans lequel une
 * chasse de palier 1 ne rapporterait jamais rien.
 *
 * **Un arbre non ouvert ne recoit rien** : la `DomainExperience` n'existe que
 * pour les arbres dont le joueur a lu le parchemin, et c'est la meme regle que
 * la recolte suit depuis toujours (`DomainExperienceEvolver`).
 */
class DomainPointGranter implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatGestureLedger $gestureLedger,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MobDeadEvent::NAME => 'onMobDead',
        ];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();

        // Anti-exploit : une invocation n'est pas une rencontre.
        if ($mob->isSummoned()) {
            return;
        }

        $fight = $mob->getFight();
        if ($fight === null) {
            return;
        }

        $quarters = DomainPointYield::quartersFor($mob->getMonster()->getTier());
        if ($quarters <= 0) {
            // T0 : les mannequins d'entrainement. Ils enseignent, ils ne font
            // pas progresser (GAME_BESTIARY).
            return;
        }

        $alivePlayers = $fight->getPlayers()->filter(fn (Player $p) => !$p->isDead());
        if ($fight->isCoopFight() && $alivePlayers->count() > 1) {
            $quarters = max(1, (int) round($quarters / $alivePlayers->count()));
        }

        $granted = false;
        foreach ($alivePlayers as $player) {
            $domainId = $this->gestureLedger->caseFor($fight, $player);
            $granted = $this->grantTo($player, $domainId, $quarters) || $granted;
        }

        if ($granted) {
            $this->entityManager->flush();
        }
    }

    /**
     * Crediter un joueur, s'il a une case et s'il a ouvert l'arbre.
     */
    private function grantTo(Player $player, ?int $domainId, int $quarters): bool
    {
        if ($domainId === null) {
            return false;
        }

        $domainExperience = $this->openedTree($player, $domainId);
        if ($domainExperience === null) {
            return false;
        }

        $oldLevel = $domainExperience->getLevel();
        $domainExperience->addQuarters($quarters);
        $this->entityManager->persist($domainExperience);

        $newLevel = $domainExperience->getLevel();
        if ($newLevel > $oldLevel) {
            $this->eventDispatcher->dispatch(
                new DomainLevelUpEvent($player, $domainExperience->getDomain(), $oldLevel, $newLevel),
                DomainLevelUpEvent::NAME
            );
        }

        return true;
    }

    private function openedTree(Player $player, int $domainId): ?DomainExperience
    {
        foreach ($player->getDomainExperiences() as $domainExperience) {
            if ($domainExperience->getDomain()->getId() === $domainId) {
                return $domainExperience;
            }
        }

        return null;
    }
}
