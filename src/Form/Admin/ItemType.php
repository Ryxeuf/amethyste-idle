<?php

namespace App\Form\Admin;

use App\Entity\Game\Domain;
use App\Entity\Game\Item;
use App\Entity\Game\Spell;
use App\Enum\Element;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Objet' => Item::TYPE_STUFF,
                    'Equipement' => Item::TYPE_GEAR_PIECE,
                    'Materia' => Item::TYPE_MATERIA,
                ],
            ])
            ->add('element', EnumType::class, [
                'label' => 'Element',
                'class' => Element::class,
                'choice_label' => fn (Element $e) => $e->label(),
            ])
            ->add('gearLocation', ChoiceType::class, [
                'label' => 'Emplacement',
                'required' => false,
                'placeholder' => '-- Aucun --',
                'choices' => array_combine(
                    array_map(fn (string $loc) => ucfirst(str_replace('_', ' ', $loc)), Item::GEAR_LOCATIONS),
                    Item::GEAR_LOCATIONS
                ),
            ])
            ->add('price', IntegerType::class, ['label' => 'Prix', 'required' => false])
            ->add('protection', IntegerType::class, ['label' => 'Protection', 'required' => false])
            ->add('energyCost', IntegerType::class, ['label' => 'Cout energie', 'required' => false])
            ->add('space', IntegerType::class, ['label' => 'Espace'])
            ->add('level', IntegerType::class, ['label' => 'Niveau', 'required' => false])
            ->add('nbUsages', IntegerType::class, ['label' => 'Nb utilisations (-1 = infini)'])
            ->add('effect', TextareaType::class, ['label' => 'Effet', 'required' => false])
            ->add('spell', EntityType::class, [
                'class' => Spell::class,
                'choice_label' => 'name',
                'label' => 'Sort associe',
                'required' => false,
                'placeholder' => '-- Aucun --',
            ])
            ->add('domain', EntityType::class, [
                'class' => Domain::class,
                'choice_label' => 'title',
                'label' => 'Domaine',
                'required' => false,
                'placeholder' => '-- Aucun --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class,
        ]);
    }
}
