<?php

namespace App\Form\Admin;

use App\Entity\Game\Monster;
use App\Entity\Game\Spell;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonsterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('life', IntegerType::class, ['label' => 'Points de vie'])
            ->add('hit', IntegerType::class, ['label' => 'Precision'])
            ->add('speed', IntegerType::class, ['label' => 'Vitesse'])
            ->add('level', IntegerType::class, ['label' => 'Niveau'])
            ->add('attack', EntityType::class, [
                'class' => Spell::class,
                'choice_label' => 'name',
                'label' => 'Attaque de base',
                'required' => false,
                'placeholder' => '-- Aucune --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Monster::class,
        ]);
    }
}
