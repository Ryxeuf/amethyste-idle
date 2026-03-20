<?php

namespace App\Form\Admin;

use App\Entity\App\Map;
use App\Entity\App\Mob;
use App\Entity\Game\Monster;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MobSpawnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('monster', EntityType::class, [
                'class' => Monster::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('m')->orderBy('m.name', 'ASC'),
                'label' => 'Monstre',
                'attr' => ['class' => 'bg-gray-800 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 w-full'],
            ])
            ->add('coordinates', TextType::class, [
                'label' => 'Coordonnees (x.y)',
                'attr' => ['class' => 'bg-gray-800 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 w-full', 'placeholder' => '10.15'],
            ])
            ->add('level', IntegerType::class, [
                'label' => 'Niveau',
                'attr' => ['class' => 'bg-gray-800 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 w-full'],
            ]);

        if ($options['show_map_field']) {
            $builder->add('map', EntityType::class, [
                'class' => Map::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('m')->orderBy('m.name', 'ASC'),
                'label' => 'Carte',
                'attr' => ['class' => 'bg-gray-800 border border-gray-700 text-gray-200 rounded-lg px-3 py-2 w-full'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mob::class,
            'show_map_field' => false,
        ]);
    }
}
