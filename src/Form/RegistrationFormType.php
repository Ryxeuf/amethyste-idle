<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * ONB-01 — trois champs, pas un de plus.
 *
 * Pas de pseudo de compte (le nom qui compte est celui du personnage, pas 2),
 * pas de confirmation de mot de passe (un bouton « afficher » remplace la
 * double saisie, qui fait abandonner plus qu'elle ne corrige).
 */
class RegistrationFormType extends AbstractType
{
    /** Longueur minimale du mot de passe (cadrage ONB-01). */
    public const PASSWORD_MIN_LENGTH = 10;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'registration.email',
                'attr' => ['autocomplete' => 'email', 'autofocus' => true],
                'constraints' => [
                    new NotBlank(message: 'Indiquez une adresse e-mail.'),
                    new Email(message: 'Cette adresse e-mail n\'est pas valide.'),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // Lu puis hache par le controleur : jamais porte par l'entite.
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(message: 'Choisissez un mot de passe.'),
                    new Length(
                        min: self::PASSWORD_MIN_LENGTH,
                        max: 4096,
                        minMessage: 'Le mot de passe doit comporter au moins {{ limit }} caracteres.',
                    ),
                ],
                'label' => 'registration.password',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les regles du jeu.'),
                ],
                'label' => 'registration.agree_terms',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
