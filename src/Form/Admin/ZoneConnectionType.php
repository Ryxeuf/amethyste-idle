<?php

namespace App\Form\Admin;

use App\Entity\App\Zone;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Liaison du graphe de zones, cote administration.
 *
 * Le formulaire n'est pas mappe sur `ZoneConnection` : son constructeur exige
 * les deux zones et interdit la boucle sur soi-meme, ce qu'un formulaire mappe
 * ne peut pas satisfaire avant validation. Le controleur construit l'arete.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class ZoneConnectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('toZone', EntityType::class, [
                'class' => Zone::class,
                'choice_label' => fn (Zone $zone) => sprintf('%s (%s)', $zone->getName(), $zone->getSlug()),
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('z')->orderBy('z.name', 'ASC'),
                'label' => 'Zone de destination',
                'placeholder' => 'Choisir une zone',
                'constraints' => [new NotNull(['message' => 'Choisissez une zone de destination.'])],
            ])
            ->add('travelSeconds', IntegerType::class, [
                'label' => 'Duree du voyage (secondes)',
                'help' => '0 = passage instantane (entrer dans un batiment).',
                'constraints' => [new Range(['min' => 0, 'max' => 86400])],
            ])
            ->add('requiresDiscovery', CheckboxType::class, [
                'label' => 'Liaison rapide (necessite d\'avoir decouvert la destination)',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);

        // Le sens retour ne se propose qu'a la creation : sur une arete
        // existante, il n'y a rien a dupliquer.
        if ($options['allow_bidirectional']) {
            $builder->add('bidirectional', CheckboxType::class, [
                'label' => 'Creer aussi le sens retour',
                'required' => false,
                'help' => 'Une liaison est orientee : sans le retour, le joueur ne peut pas revenir.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'allow_bidirectional' => true,
        ]);
        $resolver->setAllowedTypes('allow_bidirectional', 'bool');
    }
}
