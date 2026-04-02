<?php

namespace App\Form;

use App\Entity\Game\Race;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CharacterCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du personnage',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir un nom pour votre personnage.']),
                    new Length([
                        'min' => 3,
                        'max' => 16,
                        'minMessage' => 'Le nom doit comporter au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Regex([
                        'pattern' => '/^[\p{L}\s\-]+$/u',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets.',
                    ]),
                ],
                'attr' => [
                    'placeholder' => 'Ex: Aldric, Elara, Thorin...',
                    'maxlength' => 16,
                    'minlength' => 3,
                    'autocomplete' => 'off',
                ],
            ])
            ->add('race', EntityType::class, [
                'class' => Race::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('r')
                    ->where('r.availableAtCreation = true')
                    ->orderBy('r.name', 'ASC'),
                'expanded' => true,
                'label' => 'Race',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une race.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
