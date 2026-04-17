<?php

namespace App\Form;

use App\Entity\Game\Race;
use App\Service\Avatar\AvatarCatalogProvider;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CharacterCreateType extends AbstractType
{
    /**
     * Palette de couleurs de cheveux proposees a la creation.
     * Synchronisee avec le tint applique par PlayerAvatarPayloadBuilder.
     *
     * @var array<string, string>
     */
    public const HAIR_COLORS = [
        'Blond' => '#d6b25e',
        'Chatain' => '#8b5a2b',
        'Brun' => '#3b2616',
        'Noir' => '#1a1410',
        'Roux' => '#b4441f',
        'Argent' => '#c9cdd4',
    ];

    public function __construct(
        private readonly AvatarCatalogProvider $avatarCatalogProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->avatarCatalogProvider->getCreationChoices();

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
            ])
            ->add('body', ChoiceType::class, self::buildAvatarChoiceOptions(
                'Corps',
                $choices['body'],
                'Veuillez choisir un corps.',
                required: true,
            ))
            ->add('hair', ChoiceType::class, self::buildAvatarChoiceOptions(
                'Coiffure',
                $choices['hair'],
                'Veuillez choisir une coiffure.',
                required: false,
            ))
            ->add('hairColor', ChoiceType::class, [
                'label' => 'Couleur de cheveux',
                'choices' => self::HAIR_COLORS,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Choice([
                        'choices' => array_values(self::HAIR_COLORS),
                        'message' => 'Couleur de cheveux invalide.',
                    ]),
                ],
            ])
            ->add('outfit', ChoiceType::class, self::buildAvatarChoiceOptions(
                'Tenue de depart',
                $choices['outfit'],
                'Veuillez choisir une tenue.',
                required: false,
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }

    /**
     * @param list<array{slug: string, sheet: string}> $entries
     *
     * @return array<string, mixed>
     */
    private static function buildAvatarChoiceOptions(
        string $label,
        array $entries,
        string $invalidMessage,
        bool $required,
    ): array {
        $choices = [];
        foreach ($entries as $entry) {
            $choices[$entry['slug']] = $entry['slug'];
        }

        $options = [
            'label' => $label,
            'choices' => $choices,
            'choice_attr' => static function (string $slug) use ($entries): array {
                foreach ($entries as $entry) {
                    if ($entry['slug'] === $slug) {
                        return ['data-sheet' => $entry['sheet']];
                    }
                }

                return [];
            },
            'expanded' => true,
            'required' => $required,
            'placeholder' => false,
        ];

        if ($choices !== []) {
            $options['constraints'] = [
                new Choice([
                    'choices' => array_values($choices),
                    'message' => $invalidMessage,
                ]),
            ];
        }

        return $options;
    }
}
