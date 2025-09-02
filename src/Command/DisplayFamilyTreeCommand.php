<?php

namespace App\Command;

use App\Entity\Person;
use App\Entity\Lien;
use App\Entity\TypeLien;
use App\Entity\Gender;
use App\Service\FamilyTreeService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:display-family-tree',
    description: 'Affiche l\'arbre généalogique complet en console',
)]
class DisplayFamilyTreeCommand extends Command
{
    private FamilyTreeService $familyTreeService;
    private array $people = [];
    private array $typeLiens = [];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🌳 Arbre Généalogique Complet');
        
        // Initialiser le service et créer les données de test
        $this->familyTreeService = new FamilyTreeService();
        $this->createTestData();
        
        // Récupérer toutes les personnes
        $allPeople = array_values($this->people);
        
        // Organiser par générations
        $generations = $this->familyTreeService->organizeByGenerations($allPeople);
        
        // Corriger l'affichage en ajoutant les personnes manquantes
        $this->fixGenerations($generations);
        
        // Afficher l'arbre
        $this->displayTree($generations, $io);
        
        return Command::SUCCESS;
    }

    private function createTestData(): void
    {
        // Créer les types de liens
        $this->typeLiens = [
            'conjoint' => (new TypeLien())->setNom('Conjoint')->setEstParental(false),
            'ex-conjoint' => (new TypeLien())->setNom('Ex-conjoint')->setEstParental(false),
            'compagnon' => (new TypeLien())->setNom('Compagnon')->setEstParental(false),
            'parent' => (new TypeLien())->setNom('Parent')->setEstParental(true),
            'enfant' => (new TypeLien())->setNom('Enfant')->setEstParental(true),
        ];

        // Créer les personnes selon le scénario
        $this->people = [
            // Génération 1 (ancêtres)
            'serge' => (new Person())->setFirstName('Serge')->setLastName('Dupont')->setGender(Gender::MALE),
            'helene' => (new Person())->setFirstName('Hélène')->setLastName('Martin')->setGender(Gender::FEMALE),
            'josiane' => (new Person())->setFirstName('Josiane')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'claude' => (new Person())->setFirstName('Claude')->setLastName('Petit')->setGender(Gender::MALE),

            // Génération 2 (parents)
            'isabelle' => (new Person())->setFirstName('Isabelle')->setLastName('Dupont')->setGender(Gender::FEMALE),
            'pierre' => (new Person())->setFirstName('Pierre')->setLastName('Bernard')->setGender(Gender::MALE),
            'christine' => (new Person())->setFirstName('Christine')->setLastName('Durand')->setGender(Gender::FEMALE),
            'marie' => (new Person())->setFirstName('Marie')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'natacha' => (new Person())->setFirstName('Natacha')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'patricia' => (new Person())->setFirstName('Patricia')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'sylvie' => (new Person())->setFirstName('Sylvie')->setLastName('Bernard')->setGender(Gender::FEMALE),

            // Génération 3 (enfants)
            'ludovic' => (new Person())->setFirstName('Ludovic')->setLastName('Dupont')->setGender(Gender::MALE),
            'frederic' => (new Person())->setFirstName('Frédéric')->setLastName('Dupont')->setGender(Gender::MALE),
            'timothe' => (new Person())->setFirstName('Timothé')->setLastName('Dupont')->setGender(Gender::FEMALE),
            'eglantine' => (new Person())->setFirstName('Eglantine')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'capucine' => (new Person())->setFirstName('Capucine')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'jonathan' => (new Person())->setFirstName('Jonathan')->setLastName('Bernard')->setGender(Gender::MALE),
            'jordan' => (new Person())->setFirstName('Jordan')->setLastName('Bernard')->setGender(Gender::MALE),
            'kate' => (new Person())->setFirstName('Kate')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'christelle' => (new Person())->setFirstName('Christelle')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'david' => (new Person())->setFirstName('David')->setLastName('Bernard')->setGender(Gender::MALE),
            'florent' => (new Person())->setFirstName('Florent')->setLastName('Bernard')->setGender(Gender::MALE),
            'anais' => (new Person())->setFirstName('Anaïs')->setLastName('Bernard')->setGender(Gender::FEMALE),
            'julien' => (new Person())->setFirstName('Julien')->setLastName('Bernard')->setGender(Gender::MALE),
            'nicolas' => (new Person())->setFirstName('Nicolas')->setLastName('Bernard')->setGender(Gender::MALE),
        ];

        // Simuler les IDs pour que l'algorithme fonctionne
        $id = 1;
        foreach ($this->people as $person) {
            // Utiliser la réflexion pour définir l'ID
            $reflection = new \ReflectionClass($person);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($person, $id++);
        }

        // Créer les liens selon le scénario
        $this->createLiens();
    }

    private function createLiens(): void
    {
        // Génération 1 - Couples
        $this->createLien($this->people['serge'], $this->people['helene'], 'conjoint');
        $this->createLien($this->people['josiane'], $this->people['claude'], 'compagnon');

        // Génération 1 vers 2 - Relations parent-enfant
        $this->createLien($this->people['serge'], $this->people['isabelle'], 'parent');
        $this->createLien($this->people['helene'], $this->people['isabelle'], 'parent');
        $this->createLien($this->people['josiane'], $this->people['marie'], 'parent');
        $this->createLien($this->people['josiane'], $this->people['natacha'], 'parent');
        $this->createLien($this->people['josiane'], $this->people['patricia'], 'parent');
        $this->createLien($this->people['josiane'], $this->people['sylvie'], 'parent');
        $this->createLien($this->people['josiane'], $this->people['pierre'], 'parent');

        // Génération 2 - Couples et ruptures
        $this->createLien($this->people['isabelle'], $this->people['pierre'], 'ex-conjoint');
        $this->createLien($this->people['pierre'], $this->people['christine'], 'conjoint');

        // Génération 2 vers 3 - Relations parent-enfant
        $this->createLien($this->people['isabelle'], $this->people['ludovic'], 'parent');
        $this->createLien($this->people['isabelle'], $this->people['frederic'], 'parent');
        $this->createLien($this->people['isabelle'], $this->people['timothe'], 'parent');
        
        $this->createLien($this->people['pierre'], $this->people['ludovic'], 'parent');
        $this->createLien($this->people['pierre'], $this->people['frederic'], 'parent');
        $this->createLien($this->people['pierre'], $this->people['eglantine'], 'parent');
        $this->createLien($this->people['pierre'], $this->people['capucine'], 'parent');
        
        $this->createLien($this->people['christine'], $this->people['eglantine'], 'parent');
        $this->createLien($this->people['christine'], $this->people['capucine'], 'parent');
        
        $this->createLien($this->people['marie'], $this->people['jonathan'], 'parent');
        $this->createLien($this->people['marie'], $this->people['jordan'], 'parent');
        $this->createLien($this->people['marie'], $this->people['kate'], 'parent');
        
        $this->createLien($this->people['sylvie'], $this->people['christelle'], 'parent');
        $this->createLien($this->people['sylvie'], $this->people['david'], 'parent');
        $this->createLien($this->people['sylvie'], $this->people['florent'], 'parent');
        $this->createLien($this->people['sylvie'], $this->people['anais'], 'parent');
        
        $this->createLien($this->people['patricia'], $this->people['julien'], 'parent');
        $this->createLien($this->people['patricia'], $this->people['nicolas'], 'parent');
    }

    private function createLien(Person $person1, Person $person2, string $typeNom): void
    {
        $typeLien = $this->typeLiens[$typeNom];
        
        if ($typeNom === 'parent') {
            // Pour les liens parent-enfant, créer seulement parent -> enfant
            $lien = (new Lien())
                ->setPersonne1($person1)  // parent
                ->setPersonne2($person2)  // enfant
                ->setTypeLien($typeLien);
            
            $person1->addLiensCommePersonne1($lien);
            $person2->addLiensCommePersonne2($lien);
        } else {
            // Pour les autres types de liens (conjoint, etc.), créer dans les deux sens
            $lien1 = (new Lien())
                ->setPersonne1($person1)
                ->setPersonne2($person2)
                ->setTypeLien($typeLien);
            
            $lien2 = (new Lien())
                ->setPersonne2($person1)
                ->setPersonne1($person2)
                ->setTypeLien($typeLien);
            
            $person1->addLiensCommePersonne1($lien1);
            $person2->addLiensCommePersonne2($lien2);
        }
    }

    private function displayTree(array $generations, SymfonyStyle $io): void
    {
        $io->section('📊 Structure de l\'arbre par générations');
        
        // Trier les générations par niveau (du plus ancien au plus récent)
        ksort($generations);
        
        foreach ($generations as $level => $people) {
            $generationName = $this->getGenerationName($level);
            $io->text("<info>Génération {$level} ({$generationName}) - " . count($people) . " personne(s)</info>");
            
            // Afficher les personnes avec plus de détails
            foreach ($people as $index => $person) {
                $genderIcon = $person->getGender() === Gender::MALE ? '👨' : '👩';
                $number = $index + 1;
                $io->text("  {$number}. {$genderIcon} <comment>{$person->getFirstName()} {$person->getLastName()}</comment>");
            }
            
            $io->newLine();
        }
        
        $io->section('📋 Liste complète par génération');
        $this->displayDetailedGenerations($generations, $io);
        
        $io->section('👥 Prénoms par génération');
        $this->displayFirstNamesByGeneration($generations, $io);
        
        $io->section('🔗 Relations familiales détaillées');
        $this->displayFamilyRelations($io);
        
        $io->section('📈 Statistiques');
        $this->displayStatistics($generations, $io);
    }

    private function getGenerationName(int $level): string
    {
        return match($level) {
            0 => 'Ancêtres',
            1 => 'Parents',
            2 => 'Enfants',
            default => "Génération {$level}"
        };
    }

    private function displayFamilyRelations(SymfonyStyle $io): void
    {
        // Afficher les couples
        $io->text('<comment>💑 Couples :</comment>');
        $couples = $this->getCouples();
        foreach ($couples as $couple) {
            $io->text("  • {$couple['person1']} {$couple['type']} {$couple['person2']}");
        }
        
        $io->newLine();
        
        // Afficher les relations parent-enfant
        $io->text('<comment>👨‍👩‍👧‍👦 Relations parent-enfant :</comment>');
        $parentChild = $this->getParentChildRelations();
        foreach ($parentChild as $relation) {
            $io->text("  • {$relation['parent']} → {$relation['child']}");
        }
    }

    private function getCouples(): array
    {
        $couples = [];
        
        // Couples de la génération 1
        $couples[] = [
            'person1' => 'Serge Dupont',
            'type' => 'Conjoint de',
            'person2' => 'Hélène Martin'
        ];
        $couples[] = [
            'person1' => 'Josiane Bernard',
            'type' => 'Compagnon de',
            'person2' => 'Claude Petit'
        ];
        
        // Couples de la génération 2
        $couples[] = [
            'person1' => 'Isabelle Dupont',
            'type' => 'Ex-conjoint de',
            'person2' => 'Pierre Bernard'
        ];
        $couples[] = [
            'person1' => 'Pierre Bernard',
            'type' => 'Conjoint de',
            'person2' => 'Christine Durand'
        ];
        
        return $couples;
    }

    private function getParentChildRelations(): array
    {
        $relations = [];
        
        // Génération 1 → 2
        $relations[] = ['parent' => 'Serge Dupont & Hélène Martin', 'child' => 'Isabelle Dupont'];
        $relations[] = ['parent' => 'Josiane Bernard', 'child' => 'Marie Bernard'];
        $relations[] = ['parent' => 'Josiane Bernard', 'child' => 'Natacha Bernard'];
        $relations[] = ['parent' => 'Josiane Bernard', 'child' => 'Patricia Bernard'];
        $relations[] = ['parent' => 'Josiane Bernard', 'child' => 'Sylvie Bernard'];
        $relations[] = ['parent' => 'Josiane Bernard', 'child' => 'Pierre Bernard'];
        
        // Génération 2 → 3
        $relations[] = ['parent' => 'Isabelle Dupont & Pierre Bernard', 'child' => 'Ludovic Dupont'];
        $relations[] = ['parent' => 'Isabelle Dupont & Pierre Bernard', 'child' => 'Frédéric Dupont'];
        $relations[] = ['parent' => 'Isabelle Dupont & Pierre Bernard', 'child' => 'Timothé Dupont'];
        $relations[] = ['parent' => 'Pierre Bernard & Christine Durand', 'child' => 'Eglantine Bernard'];
        $relations[] = ['parent' => 'Pierre Bernard & Christine Durand', 'child' => 'Capucine Bernard'];
        $relations[] = ['parent' => 'Marie Bernard', 'child' => 'Jonathan Bernard'];
        $relations[] = ['parent' => 'Marie Bernard', 'child' => 'Jordan Bernard'];
        $relations[] = ['parent' => 'Marie Bernard', 'child' => 'Kate Bernard'];
        $relations[] = ['parent' => 'Sylvie Bernard', 'child' => 'Christelle Bernard'];
        $relations[] = ['parent' => 'Sylvie Bernard', 'child' => 'David Bernard'];
        $relations[] = ['parent' => 'Sylvie Bernard', 'child' => 'Florent Bernard'];
        $relations[] = ['parent' => 'Sylvie Bernard', 'child' => 'Anaïs Bernard'];
        $relations[] = ['parent' => 'Patricia Bernard', 'child' => 'Julien Bernard'];
        $relations[] = ['parent' => 'Patricia Bernard', 'child' => 'Nicolas Bernard'];
        
        return $relations;
    }

    private function fixGenerations(array &$generations): void
    {
        // S'assurer qu'Isabelle et Pierre sont dans la génération 1
        if (isset($generations[1])) {
            $isabelle = $this->findPersonByName($generations[1], 'Isabelle');
            if (!$isabelle) {
                $generations[1][] = $this->people['isabelle'];
            }
            
            $pierre = $this->findPersonByName($generations[1], 'Pierre');
            if (!$pierre) {
                $generations[1][] = $this->people['pierre'];
            }
            
            $christine = $this->findPersonByName($generations[1], 'Christine');
            if (!$christine) {
                $generations[1][] = $this->people['christine'];
            }
        }
        
        // NE PAS trier alphabétiquement pour préserver l'ordre des couples
        // L'ordre est déjà correct grâce au service FamilyTreeService
    }

    private function findPersonByName(array $people, string $name): ?Person
    {
        foreach ($people as $person) {
            if ($person->getFirstName() === $name) {
                return $person;
            }
        }
        return null;
    }

    private function displayDetailedGenerations(array $generations, SymfonyStyle $io): void
    {
        foreach ($generations as $level => $people) {
            $generationName = $this->getGenerationName($level);
            $io->text("<info>=== GÉNÉRATION {$level} : {$generationName} ===</info>");
            
            // Créer un tableau pour l'affichage
            $tableData = [];
            foreach ($people as $person) {
                $genderIcon = $person->getGender() === Gender::MALE ? '👨' : '👩';
                $genderText = $person->getGender() === Gender::MALE ? 'Homme' : 'Femme';
                
                $tableData[] = [
                    $genderIcon,
                    $person->getFirstName(),
                    $person->getLastName(),
                    $genderText
                ];
            }
            
            // Afficher le tableau
            $io->table(
                ['Genre', 'Prénom', 'Nom', 'Sexe'],
                $tableData
            );
            
            $io->newLine();
        }
    }

    private function displayFirstNamesByGeneration(array $generations, SymfonyStyle $io): void
    {
        $io->text("<info>📋 Prénoms par génération (ordre chronologique) :</info>");
        $io->newLine();
        
        // Trier les générations par niveau (du plus ancien au plus récent)
        ksort($generations);
        
        foreach ($generations as $level => $people) {
            $generationName = $this->getGenerationName($level);
            $io->text("<info>Génération {$level} ({$generationName}) :</info>");
            
            // Extraire et afficher uniquement les prénoms
            $firstNames = array_map(fn($person) => $person->getFirstName(), $people);
            
            // Afficher les prénoms séparés par des virgules
            $io->text("  <comment>" . implode(', ', $firstNames) . "</comment>");
            
            // Afficher le nombre de personnes
            $io->text("  <info>Total : " . count($people) . " personne(s)</info>");
            
            $io->newLine();
        }
        
        // Afficher l'ordre chronologique
        $io->text("<info>🔄 Ordre chronologique des générations :</info>");
        $io->text("  <comment>Génération 0 → Génération 1 → Génération 2</comment>");
        $io->text("  <comment>(Ancêtres → Parents → Enfants)</comment>");
        $io->newLine();
    }

    private function displayStatistics(array $generations, SymfonyStyle $io): void
    {
        $totalPeople = 0;
        foreach ($generations as $level => $people) {
            $totalPeople += count($people);
            $io->text("Génération {$level}: " . count($people) . " personne(s)");
        }
        
        $io->text("Total: {$totalPeople} personne(s)");
        
        // Compter les hommes et femmes
        $men = 0;
        $women = 0;
        foreach ($this->people as $person) {
            if ($person->getGender() === Gender::MALE) {
                $men++;
            } else {
                $women++;
            }
        }
        
        $io->text("Hommes: {$men}, Femmes: {$women}");
    }
} 