<?php

namespace App\Form\Admin;

use App\Entity\Game\Spell;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SpellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('damage', IntegerType::class, ['label' => 'Degats', 'required' => false])
            ->add('heal', IntegerType::class, ['label' => 'Soin', 'required' => false])
            ->add('hit', IntegerType::class, ['label' => 'Precision'])
            ->add('critical', IntegerType::class, ['label' => 'Critique (%)'])
            ->add('spellRange', IntegerType::class, ['label' => 'Portee', 'required' => false])
            ->add('element', ChoiceType::class, [
                'label' => 'Element',
                'choices' => [
                    'Aucun' => Spell::ELEMENT_NONE,
                    'Feu' => Spell::ELEMENT_FIRE,
                    'Eau' => Spell::ELEMENT_WATER,
                    'Terre' => Spell::ELEMENT_EARTH,
                    'Air' => Spell::ELEMENT_AIR,
                    'Lumiere' => Spell::ELEMENT_LIGHT,
                    'Tenebres' => Spell::ELEMENT_DARK,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Spell::class,
        ]);
    }
}
