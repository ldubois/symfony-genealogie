<?php

namespace App\Form;

use App\Entity\Person;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Gender;
use App\Repository\PersonRepository;

class PersonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Entrez le prénom']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Entrez le nom']
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Homme' => Gender::MALE,
                    'Femme' => Gender::FEMALE
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'attr' => ['class' => 'gender-choice']
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'placeholder' => 'jj/mm/aaaa',
                    'class' => 'form-control flatpickr'
                ]
            ])
            ->add('birthPlace', TextType::class, [
                'label' => 'Lieu de naissance',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez le lieu de naissance']
            ])
            ->add('deathDate', DateType::class, [
                'label' => 'Date de décès',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'placeholder' => 'jj/mm/aaaa',
                    'class' => 'form-control flatpickr'
                ]
            ])
            ->add('deathPlace', TextType::class, [
                'label' => 'Lieu de décès',
                'required' => false,
                'attr' => ['placeholder' => 'Entrez le lieu de décès']
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'Biographie',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez la biographie',
                    'rows' => 5
                ]
            ])
            ->add('father', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'fullName',
                'label' => 'Père',
                'required' => false,
                'placeholder' => 'Sélectionnez le père',
                'query_builder' => function (PersonRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('p')
                        ->where('p.gender = :gender')
                        ->setParameter('gender', Gender::MALE)
                        ->orderBy('p.lastName', 'ASC')
                        ->addOrderBy('p.firstName', 'ASC');
                    
                    // Exclure la personne courante si elle existe
                    if (isset($options['data']) && $options['data']->getId()) {
                        $qb->andWhere('p.id != :currentId')
                           ->setParameter('currentId', $options['data']->getId());
                    }
                    
                    return $qb;
                }
            ])
            ->add('mother', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'fullName',
                'label' => 'Mère',
                'required' => false,
                'placeholder' => 'Sélectionnez la mère',
                'query_builder' => function (PersonRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('p')
                        ->where('p.gender = :gender')
                        ->setParameter('gender', Gender::FEMALE)
                        ->orderBy('p.lastName', 'ASC')
                        ->addOrderBy('p.firstName', 'ASC');
                    
                    // Exclure la personne courante si elle existe
                    if (isset($options['data']) && $options['data']->getId()) {
                        $qb->andWhere('p.id != :currentId')
                           ->setParameter('currentId', $options['data']->getId());
                    }
                    
                    return $qb;
                }
            ])
            ->add('photo', TextType::class, [
                'label' => 'Photo',
                'required' => false,
                'attr' => ['placeholder' => 'URL de la photo']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Person::class,
        ]);
    }
} 