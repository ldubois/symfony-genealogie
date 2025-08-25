<?php

namespace App\Form;

use App\Entity\Lien;
use App\Entity\Person;
use App\Entity\TypeLien;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuickLienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeLien', EntityType::class, [
                'class' => TypeLien::class,
                'choice_label' => 'nom',
                'label' => 'Type de lien',
                'placeholder' => 'Choisir un type de lien...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('personne2', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'fullName',
                'label' => 'Personne',
                'placeholder' => 'Choisir une personne...',
                'attr' => ['class' => 'form-select'],
                'query_builder' => function ($repository) use ($options) {
                    $qb = $repository->createQueryBuilder('p')
                        ->orderBy('p.firstName', 'ASC')
                        ->addOrderBy('p.lastName', 'ASC');
                    
                    // Exclure la personne actuelle si elle est définie
                    if (isset($options['exclude_person']) && $options['exclude_person']) {
                        $qb->andWhere('p.id != :excludeId')
                           ->setParameter('excludeId', $options['exclude_person']->getId());
                    }
                    
                    return $qb;
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lien::class,
            'exclude_person' => null,
        ]);
    }
}