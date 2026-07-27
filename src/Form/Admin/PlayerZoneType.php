<?php

namespace App\Form\Admin;

use App\Entity\App\Player;
use App\Entity\App\Zone;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Teleportation d'un joueur vers une zone.
 *
 * Remplace l'ancien couple carte + coordonnees : depuis le pivot, la position
 * de reference est la zone (regle projet #7), et deplacer un joueur « en 85.34 »
 * ne changeait plus rien de ce qu'il pouvait faire.
 */
class PlayerZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('currentZone', EntityType::class, [
            'class' => Zone::class,
            'choice_label' => fn (Zone $zone) => sprintf(
                '%s (%s)%s',
                $zone->getName(),
                $zone->getSlug(),
                $zone->isEnabled() ? '' : ' — desactivee',
            ),
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('z')->orderBy('z.name', 'ASC'),
            'label' => 'Zone',
            'placeholder' => 'Choisir une zone',
            'constraints' => [new NotNull(['message' => 'Choisissez une zone.'])],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Player::class]);
    }
}
