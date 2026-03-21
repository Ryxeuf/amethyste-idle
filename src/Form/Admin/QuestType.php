<?php

namespace App\Form\Admin;

use App\Entity\Game\Quest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('requirementsJson', TextareaType::class, [
                'label' => 'Conditions (JSON)',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 6, 'class' => 'font-mono'],
            ])
            ->add('rewardsJson', TextareaType::class, [
                'label' => 'Recompenses (JSON)',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 6, 'class' => 'font-mono'],
            ])
            ->add('prerequisiteQuestsJson', TextareaType::class, [
                'label' => 'Prerequis (IDs JSON, ex: [1, 2])',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 2, 'class' => 'font-mono'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quest::class,
        ]);
    }
}
