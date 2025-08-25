<?php

namespace App\DataFixtures;

use App\Entity\TypeLien;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TypeLienFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $typesLiens = [
            [
                'nom' => 'Père',
                'description' => 'Père biologique',
                'estBiologique' => true,
                'estParental' => true
            ],
            [
                'nom' => 'Mère',
                'description' => 'Mère biologique',
                'estBiologique' => true,
                'estParental' => true
            ],
            [
                'nom' => 'Père adoptif',
                'description' => 'Père par adoption',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Mère adoptive',
                'description' => 'Mère par adoption',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Beau-père',
                'description' => 'Beau-père (conjoint de la mère)',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Belle-mère',
                'description' => 'Belle-mère (conjointe du père)',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Tuteur',
                'description' => 'Tuteur légal',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Tutrice',
                'description' => 'Tutrice légale',
                'estBiologique' => false,
                'estParental' => true
            ],
            [
                'nom' => 'Parrain',
                'description' => 'Parrain religieux ou civil',
                'estBiologique' => false,
                'estParental' => false
            ],
            [
                'nom' => 'Marraine',
                'description' => 'Marraine religieuse ou civile',
                'estBiologique' => false,
                'estParental' => false
            ],
            [
                'nom' => 'Conjoint',
                'description' => 'Époux/Épouse',
                'estBiologique' => false,
                'estParental' => false
            ],
            [
                'nom' => 'Compagnon',
                'description' => 'Compagnon/Compagne (union libre)',
                'estBiologique' => false,
                'estParental' => false
            ]
        ];

        foreach ($typesLiens as $data) {
            $typeLien = new TypeLien();
            $typeLien->setNom($data['nom']);
            $typeLien->setDescription($data['description']);
            $typeLien->setEstBiologique($data['estBiologique']);
            $typeLien->setEstParental($data['estParental']);
            
            $manager->persist($typeLien);
            
            // Référence pour les autres fixtures
            $this->addReference('type-lien-' . strtolower(str_replace([' ', '-'], '_', $data['nom'])), $typeLien);
        }

        $manager->flush();
    }
}