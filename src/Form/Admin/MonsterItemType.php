<?php

namespace App\Form\Admin;

use App\Entity\Game\Item;
use App\Entity\Game\Monster;
use App\Entity\Game\MonsterItem;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MonsterItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('monster', EntityType::class, [
                'class' => Monster::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('m')->orderBy('m.name', 'ASC'),
                'label' => 'Monstre',
            ])
            ->add('item', EntityType::class, [
                'class' => Item::class,
                'choice_label' => 'name',
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('i')->orderBy('i.name', 'ASC'),
                'label' => 'Item',
            ])
            ->add('probability', NumberType::class, [
                'label' => 'Probabilite (%)',
                'scale' => 2,
                'html5' => true,
            ])
            ->add('guaranteed', CheckboxType::class, [
                'label' => 'Drop garanti',
                'required' => false,
            ])
            ->add('minDifficulty', IntegerType::class, [
                'label' => 'Difficulte minimum',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MonsterItem::class,
        ]);
    }
}
