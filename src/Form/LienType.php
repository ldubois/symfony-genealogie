<?php

namespace App\Form;

use App\Entity\Lien;
use App\Entity\Person;
use App\Entity\TypeLien;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('personne1', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'fullName',
                'label' => 'Première personne',
                'placeholder' => 'Sélectionnez une personne...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('personne2', EntityType::class, [
                'class' => Person::class,
                'choice_label' => 'fullName',
                'label' => 'Deuxième personne',
                'placeholder' => 'Sélectionnez une personne...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('typeLien', EntityType::class, [
                'class' => TypeLien::class,
                'choice_label' => 'nom',
                'label' => 'Type de relation',
                'placeholder' => 'Sélectionnez un type de relation...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'form-control flatpickr',
                    'placeholder' => 'jj/mm/aaaa'
                ],
                'help' => 'Laissez vide si la date est inconnue'
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'attr' => [
                    'class' => 'form-control flatpickr',
                    'placeholder' => 'jj/mm/aaaa'
                ],
                'help' => 'Laissez vide si la relation est toujours active'
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes ou commentaires sur cette relation...'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lien::class,
        ]);
    }
}