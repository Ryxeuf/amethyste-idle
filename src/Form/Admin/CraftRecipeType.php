<?php

namespace App\Form\Admin;

use App\Entity\Game\CraftRecipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CraftRecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 3]])
            ->add('profession', ChoiceType::class, [
                'label' => 'Profession',
                'choices' => [
                    'Forgeron' => 'blacksmith',
                    'Tanneur' => 'tanner',
                    'Alchimiste' => 'alchemist',
                    'Joaillier' => 'jeweler',
                ],
            ])
            ->add('ingredientsJson', TextareaType::class, [
                'label' => 'Ingredients (JSON)',
                'mapped' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => '[{"item_slug": "iron-ore", "quantity": 2}]',
                ],
            ])
            ->add('resultItemSlug', TextType::class, ['label' => 'Slug item resultat'])
            ->add('resultQuantity', IntegerType::class, ['label' => 'Quantite resultat'])
            ->add('requiredSkillSlug', TextType::class, ['label' => 'Slug competence requise', 'required' => false])
            ->add('requiredLevel', IntegerType::class, ['label' => 'Niveau requis'])
            ->add('craftTime', IntegerType::class, ['label' => 'Temps de craft (secondes)'])
            ->add('experienceGain', IntegerType::class, ['label' => 'XP gagnee'])
            ->add('isDiscoverable', CheckboxType::class, ['label' => 'Decouvrable par experimentation', 'required' => false])
            ->add('isDiscovered', CheckboxType::class, ['label' => 'Visible par defaut', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CraftRecipe::class,
        ]);
    }
}
