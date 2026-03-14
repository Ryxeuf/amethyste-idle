<?php

namespace App\Form\Admin;

use App\Entity\App\Map;
use App\Entity\App\Pnj;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PnjType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('classType', TextType::class, ['label' => 'Type de classe (sprite)'])
            ->add('life', IntegerType::class, ['label' => 'Vie'])
            ->add('maxLife', IntegerType::class, ['label' => 'Vie max'])
            ->add('coordinates', TextType::class, ['label' => 'Coordonnees (x.y)'])
            ->add('map', EntityType::class, [
                'class' => Map::class,
                'choice_label' => 'name',
                'label' => 'Carte',
                'required' => false,
                'placeholder' => '-- Aucune --',
            ])
            ->add('dialogJson', TextareaType::class, [
                'label' => 'Dialogue (JSON)',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 8, 'class' => 'font-mono'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pnj::class,
        ]);
    }
}
