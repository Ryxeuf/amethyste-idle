<?php

namespace App\Form;

use App\Service\Avatar\AvatarCatalogProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class CharacterCustomizeType extends AbstractType
{
    public function __construct(
        private readonly AvatarCatalogProvider $avatarCatalogProvider,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = $this->avatarCatalogProvider->getCreationChoices();

        $builder
            ->add('body', ChoiceType::class, self::buildAvatarChoiceOptions(
                'Corps',
                $choices['body'],
                'Corps invalide.',
                required: true,
            ))
            ->add('hair', ChoiceType::class, self::buildAvatarChoiceOptions(
                'Coiffure',
                $choices['hair'],
                'Coiffure invalide.',
                required: false,
            ))
            ->add('hairColor', ChoiceType::class, [
                'label' => 'Couleur de cheveux',
                'choices' => CharacterCreateType::HAIR_COLORS,
                'expanded' => true,
                'required' => false,
                'placeholder' => false,
                'constraints' => [
                    new Choice([
                        'choices' => array_values(CharacterCreateType::HAIR_COLORS),
                        'message' => 'Couleur de cheveux invalide.',
                    ]),
                ],
            ]);
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
