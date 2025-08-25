<?php

namespace App\Command;

use App\Entity\Lien;
use App\Repository\TypeLienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-redundant-links',
    description: 'Supprime les liens père/mère/frère/sœur redondants de la table Lien',
)]
class CleanRedundantLinksCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypeLienRepository $typeLienRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Types de liens à supprimer (redondants avec les relations Person)
        $redundantTypes = ['Père', 'Mère', 'Frère', 'Sœur'];
        
        $totalDeleted = 0;

        foreach ($redundantTypes as $typeName) {
            $typeLien = $this->typeLienRepository->findOneBy(['nom' => $typeName]);
            
            if ($typeLien) {
                $liens = $this->entityManager->getRepository(Lien::class)->findBy(['typeLien' => $typeLien]);
                $count = count($liens);
                
                if ($count > 0) {
                    $io->writeln("Suppression de {$count} liens de type '{$typeName}'...");
                    
                    foreach ($liens as $lien) {
                        $this->entityManager->remove($lien);
                    }
                    
                    $totalDeleted += $count;
                }
                
                // Supprimer aussi le type de lien lui-même
                $io->writeln("Suppression du type de lien '{$typeName}'...");
                $this->entityManager->remove($typeLien);
            }
        }

        if ($totalDeleted > 0) {
            $this->entityManager->flush();
            $io->success("Suppression terminée ! {$totalDeleted} liens redondants supprimés.");
        } else {
            $io->info('Aucun lien redondant trouvé.');
        }

        $io->note('Les relations père/mère/frère/sœur sont maintenant gérées uniquement par les champs de l\'entité Person.');

        return Command::SUCCESS;
    }
}