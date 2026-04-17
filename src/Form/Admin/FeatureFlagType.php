<?php

namespace App\Form\Admin;

use App\Entity\App\FeatureFlag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @extends AbstractType<FeatureFlag>
 */
class FeatureFlagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, [
                'label' => 'Slug (identifiant technique)',
                'help' => 'Minuscules, chiffres, tirets et underscores uniquement. Ex: new_combat_ui',
                'constraints' => [
                    new NotBlank(),
                    new Length(max: 100),
                    new Regex(pattern: '/^[a-z0-9_\-]+$/', message: 'Seuls minuscules, chiffres, tirets et underscores sont autorises.'),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom lisible',
                'constraints' => [new NotBlank(), new Length(max: 150)],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Active globalement (pour tous les utilisateurs)',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FeatureFlag::class,
        ]);
    }
}
