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

            // Note: Cette commande était utilisée pour migrer les anciennes relations father/mother
            // vers le nouveau système de liens. Comme ces propriétés n'existent plus,
            // cette commande n'a plus d'utilité et ne fait rien.
            $io->text("  ℹ️  Migration non nécessaire - système de liens déjà en place");
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