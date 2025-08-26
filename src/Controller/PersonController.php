<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\Lien;
use App\Entity\TypeLien;
use App\Form\PersonType;
use App\Form\QuickLienType;
use App\Repository\PersonRepository;
use App\Repository\TypeLienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\FamilyTreeService;

#[Route('/person')]
class PersonController extends AbstractController
{
    #[Route('/', name: 'app_person_index', methods: ['GET'])]
    public function index(PersonRepository $personRepository): Response
    {
        return $this->render('person/index.html.twig', [
            'people' => $personRepository->findAllOrderedByName(),
        ]);
    }

    #[Route('/arbre-complet', name: 'app_person_full_tree', methods: ['GET'])]
    public function fullTree(PersonRepository $personRepository, FamilyTreeService $familyTreeService): Response
    {
        // Récupérer toutes les personnes triées par nom
        $people = $personRepository->findAllOrderedByName();
        
        // Organiser par générations
        $generations = $familyTreeService->organizeByGenerations($people);
        
        // Obtenir les données complètes avec positions et connexions SVG
        $treeData = $familyTreeService->getConnectionData($generations);

        return $this->render('person/full_tree.html.twig', [
            'treeData' => $treeData,
        ]);
    }

    #[Route('/new', name: 'app_person_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TypeLienRepository $typeLienRepository): Response
    {
        $person = new Person();
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($person);
            $entityManager->flush();

            // Créer automatiquement les liens familiaux si des parents sont définis
            $this->createFamilyLinks($person, $entityManager, $typeLienRepository);

            $this->addFlash('success', 'La personne a été créée avec succès.');
            return $this->redirectToRoute('app_person_show', ['id' => $person->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('person/new.html.twig', [
            'person' => $person,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_person_show', methods: ['GET'])]
    public function show(Person $person, PersonRepository $personRepository): Response
    {
        $ancestors = $personRepository->findAncestors($person);
        $descendants = $personRepository->findDescendants($person);

        return $this->render('person/show.html.twig', [
            'person' => $person,
            'ancestors' => $ancestors,
            'descendants' => $descendants,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_person_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Person $person, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PersonType::class, $person);
        $form->handleRequest($request);

        // Formulaire d'ajout rapide de lien
        $quickLien = new Lien();
        $quickLien->setPersonne1($person);
        $quickLienForm = $this->createForm(QuickLienType::class, $quickLien, [
            'exclude_person' => $person
        ]);
        $quickLienForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La personne a été modifiée avec succès.');
            return $this->redirectToRoute('app_person_show', ['id' => $person->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($quickLienForm->isSubmitted() && $quickLienForm->isValid()) {
            $entityManager->persist($quickLien);
            $entityManager->flush();

            $this->addFlash('success', 'Le lien a été ajouté avec succès.');
            return $this->redirectToRoute('app_person_edit', ['id' => $person->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('person/edit.html.twig', [
            'person' => $person,
            'form' => $form,
            'quickLienForm' => $quickLienForm,
        ]);
    }

    #[Route('/{id}', name: 'app_person_delete', methods: ['POST'])]
    public function delete(Request $request, Person $person, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$person->getId(), $request->request->get('_token'))) {
            $entityManager->remove($person);
            $entityManager->flush();
            $this->addFlash('success', 'La personne a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_person_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/tree', name: 'app_person_tree', methods: ['GET'])]
    public function tree(Person $person, PersonRepository $personRepository): Response
    {
        $ancestors = $personRepository->findAncestors($person, 4);
        $descendants = $personRepository->findDescendants($person, 4);

        return $this->render('person/tree.html.twig', [
            'person' => $person,
            'ancestors' => $ancestors,
            'descendants' => $descendants,
        ]);
    }

    private function createFamilyLinks(Person $person, EntityManagerInterface $entityManager, TypeLienRepository $typeLienRepository): void
    {
        // Note: Les relations père/mère/frère/sœur sont maintenant gérées par les champs de l'entité Person
        // Cette méthode peut être utilisée pour créer d'autres types de liens si nécessaire
        
        // Exemple : créer des liens spéciaux ou des relations non-parentales
        // Pour l'instant, cette méthode ne fait rien car les relations familiales de base
        // sont gérées par father, mother et getSiblings()
    }


} 