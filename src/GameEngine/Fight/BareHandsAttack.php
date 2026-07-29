<?php

namespace App\GameEngine\Fight;

use App\Entity\CharacterInterface;
use App\Entity\Game\Spell;
use App\Event\Fight\PlayerAttackHitEvent;
use App\Event\Fight\PlayerAttackMissEvent;
use App\GameEngine\Item\ItemUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * ONB-20a — l'attaque a mains nues.
 *
 * `PlayerAttackHandler::getItem()` levait `EntityNotFoundException('Player
 * attack impossible')` des qu'aucune arme n'etait equipee : un personnage sans
 * arme n'avait **aucun** chemin de combat. C'etait supportable tant que tout le
 * monde recevait une epee ; ca ne l'est plus des qu'ONB-08 fera de l'acces a un
 * arbre — donc au port d'une arme — un acte a poser. Livrer la doctrine du
 * parchemin avant ce repli enfermerait des joueurs au lieu de les orienter.
 *
 * Ce que les mains nues sont : faible, sans emplacement de materia, **toujours
 * disponible**. Ce qu'elles ne sont pas : une arme de secours qu'on choisirait.
 * Leur chance de toucher est celle d'un geste sans entrainement — le meme
 * facteur que `ItemHitResolver` applique a un objet dont le joueur ne connait
 * pas le domaine. On ne frappe pas mieux en ne sachant rien.
 */
class BareHandsAttack
{
    /** Le sort porte par les mains nues (cf. `SpellFixtures`). */
    public const SPELL_SLUG = 'bare-hands';

    /** Libelle des evenements de combat — ce n'est pas le nom d'un objet. */
    public const LABEL = 'mains nues';

    /**
     * Un geste sans entrainement : le facteur d'`ItemHitResolver` pour un
     * domaine inconnu, applique aux chances de base.
     */
    private const UNTRAINED_FACTOR = 0.75;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SpellApplicator $spellApplicator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Aucun chemin de combat ne peut echouer faute d'arme : si le sort manque
     * aux donnees, on rate le coup — on ne leve pas.
     */
    public function strike(CharacterInterface $sender, CharacterInterface $target): bool
    {
        $spell = $this->entityManager->getRepository(Spell::class)
            ->findOneBy(['slug' => self::SPELL_SLUG]);

        if (!$spell instanceof Spell) {
            $this->eventDispatcher->dispatch(new PlayerAttackMissEvent(self::LABEL), PlayerAttackMissEvent::NAME);

            return false;
        }

        if (!ItemUtils::isActionSuccess($this->hitChances())) {
            $this->eventDispatcher->dispatch(new PlayerAttackMissEvent(self::LABEL), PlayerAttackMissEvent::NAME);

            return false;
        }

        $this->spellApplicator->apply($spell, $sender, $target);
        $this->eventDispatcher->dispatch(new PlayerAttackHitEvent(self::LABEL), PlayerAttackHitEvent::NAME);

        return true;
    }

    public function hitChances(): int
    {
        return (int) round(ItemUtils::DEFAULT_HIT_CHANCES * self::UNTRAINED_FACTOR);
    }
}
