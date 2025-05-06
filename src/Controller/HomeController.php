<?php

namespace App\Controller;

use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(PersonRepository $personRepository): Response
    {
        $latestPeople = $personRepository->findBy([], ['id' => 'DESC'], 6);
        $totalPeople = $personRepository->count([]);

        return $this->render('home/index.html.twig', [
            'latest_people' => $latestPeople,
            'total_people' => $totalPeople,
        ]);
    }
} 