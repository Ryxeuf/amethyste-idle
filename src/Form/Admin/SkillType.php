<?php

namespace App\Form\Admin;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('requiredPoints', IntegerType::class, ['label' => 'Points requis'])
            ->add('damage', IntegerType::class, ['label' => 'Degats bonus'])
            ->add('heal', IntegerType::class, ['label' => 'Soin bonus'])
            ->add('hit', IntegerType::class, ['label' => 'Precision bonus'])
            ->add('critical', IntegerType::class, ['label' => 'Critique bonus'])
            ->add('life', IntegerType::class, ['label' => 'Vie bonus'])
            ->add('domains', EntityType::class, [
                'class' => Domain::class,
                'choice_label' => 'title',
                'label' => 'Domaines',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}
