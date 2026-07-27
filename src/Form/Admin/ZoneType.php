<?php

namespace App\Form\Admin;

use App\Entity\App\Zone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Edition d'une zone du graphe de monde (pivot PBBG).
 *
 * Les traductions ne sont exposees que pour l'anglais : c'est la seule locale
 * du catalogue en plus du francais, et l'import YAML ne renseigne que `name_en`
 * / `description_en`.
 */
class ZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Identifiant stable, utilise par les fichiers YAML et les quetes. Le changer casse les references.',
                'disabled' => !$options['allow_slug_edit'],
                'constraints' => [
                    new Regex(['pattern' => '/^[a-z0-9-]+$/', 'message' => 'Minuscules, chiffres et tirets uniquement.']),
                ],
            ])
            ->add('name', TextType::class, ['label' => 'Nom (FR)'])
            ->add('nameEn', TextType::class, [
                'label' => 'Nom (EN)',
                'required' => false,
                'mapped' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Ville' => Zone::TYPE_CITY,
                    'Nature sauvage' => Zone::TYPE_WILDERNESS,
                    'Interieur' => Zone::TYPE_INTERIOR,
                    'Donjon' => Zone::TYPE_DUNGEON,
                ],
            ])
            ->add('isSafe', CheckboxType::class, [
                'label' => 'Zone sure (aucune rencontre hostile)',
                'required' => false,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'help' => 'Une zone desactivee disparait du graphe : plus aucun voyage ne la sert.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (FR)',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('descriptionEn', TextareaType::class, [
                'label' => 'Description (EN)',
                'required' => false,
                'mapped' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('illustrationPath', TextType::class, [
                'label' => 'Illustration',
                'required' => false,
                'help' => 'Chemin relatif dans public/images/ (ex: zones/foret.png).',
            ])
            ->add('mapX', IntegerType::class, [
                'label' => 'Position X sur la carte du monde (%)',
                'required' => false,
                'help' => '0 a 100. Vide = zone absente de la carte illustree.',
                'constraints' => [new Range(['min' => 0, 'max' => 100])],
            ])
            ->add('mapY', IntegerType::class, [
                'label' => 'Position Y sur la carte du monde (%)',
                'required' => false,
                'constraints' => [new Range(['min' => 0, 'max' => 100])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Zone::class,
            'allow_slug_edit' => false,
        ]);
        $resolver->setAllowedTypes('allow_slug_edit', 'bool');
    }
}
