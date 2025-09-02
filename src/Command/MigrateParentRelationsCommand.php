<?php

namespace App\Command;

use App\Entity\Person;
use App\Entity\Lien;
use App\Entity\TypeLien;
use App\Repository\PersonRepository;
use App\Repository\TypeLienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-parent-relations',
    description: 'Migre les anciennes relations parent-enfant vers le nouveau système de liens'
)]
class MigrateParentRelationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PersonRepository $personRepository,
        private TypeLienRepository $typeLienRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔄 Migration des relations parent-enfant');

        // Récupérer les types de liens pour père et mère
        $fatherType = $this->typeLienRepository->find(13); // Père biologique
        $motherType = $this->typeLienRepository->find(14); // Mère biologique
        
        if (!$fatherType || !$motherType) {
            $io->error('Types de liens Père/Mère non trouvés !');
            return Command::FAILURE;
        }

        $io->section('📋 Recherche des personnes avec des relations parent-enfant...');

        // Récupérer toutes les personnes
        $people = $this->personRepository->findAll();
        $migratedCount = 0;
        $errors = [];

        foreach ($people as $person) {
            $io->text("Vérification de {$person->getFirstName()} {$person->getLastName()}...");

            // Vérifier s'il y a un père
            if ($person->getFather()) {
                $io->text("  → Père trouvé : {$person->getFather()->getFirstName()} {$person->getFather()->getLastName()}");
                
                // Vérifier si le lien existe déjà
                $existingLien = $this->checkExistingLien($person->getFather(), $person, $fatherType);
                if (!$existingLien) {
                    // Créer le lien père → enfant
                    $lien = new Lien();
                    $lien->setPersonne1($person->getFather());
                    $lien->setPersonne2($person);
                    $lien->setTypeLien($fatherType);
                    $lien->setDateDebut(new \DateTime());
                    // Le lien est actif indéfiniment (pas de date de fin)
                    
                    $this->entityManager->persist($lien);
                    $migratedCount++;
                    $io->text("    ✅ Lien père créé");
                } else {
                    $io->text("    ℹ️  Lien père déjà existant");
                }
            }

            // Vérifier s'il y a une mère
            if ($person->getMother()) {
                $io->text("  → Mère trouvée : {$person->getMother()->getFirstName()} {$person->getMother()->getLastName()}");
                
                // Vérifier si le lien existe déjà
                $existingLien = $this->checkExistingLien($person->getMother(), $person, $motherType);
                if (!$existingLien) {
                    // Créer le lien mère → enfant
                    $lien = new Lien();
                    $lien->setPersonne1($person->getMother());
                    $lien->setPersonne2($person);
                    $lien->setTypeLien($motherType);
                    $lien->setDateDebut(new \DateTime());
                    // Le lien est actif indéfiniment (pas de date de fin)
                    
                    $this->entityManager->persist($lien);
                    $migratedCount++;
                    $io->text("    ✅ Lien mère créé");
                } else {
                    $io->text("    ℹ️  Lien mère déjà existant");
                }
            }
        }

        $io->section('💾 Sauvegarde des changements...');
        
        try {
            $this->entityManager->flush();
            $io->success("Migration terminée avec succès ! {$migratedCount} liens créés.");
        } catch (\Exception $e) {
            $io->error("Erreur lors de la sauvegarde : " . $e->getMessage());
            return Command::FAILURE;
        }

        $io->section('📊 Résumé');
        $io->text("• Liens créés : {$migratedCount}");
        $io->text("• Erreurs : " . count($errors));
        
        if (!empty($errors)) {
            $io->warning("Des erreurs sont survenues :");
            foreach ($errors as $error) {
                $io->text("  • {$error}");
            }
        }

        $io->note('Maintenant vous pouvez supprimer les anciens champs father/mother de l\'entité Person.');

        return Command::SUCCESS;
    }

    private function checkExistingLien(Person $parent, Person $child, TypeLien $parentType): ?Lien
    {
        // Vérifier si un lien parent existe déjà
        foreach ($parent->getLiensCommePersonne1() as $lien) {
            if ($lien->getPersonne2()->getId() === $child->getId() && 
                $lien->getTypeLien()->getId() === $parentType->getId()) {
                return $lien;
            }
        }

        foreach ($parent->getLiensCommePersonne2() as $lien) {
            if ($lien->getPersonne1()->getId() === $child->getId() && 
                $lien->getTypeLien()->getId() === $parentType->getId()) {
                return $lien;
            }
        }

        return null;
    }
} 