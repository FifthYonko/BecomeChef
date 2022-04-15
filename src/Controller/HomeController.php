<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security\UserAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;

class HomeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private RecetteRepository $recetteRepository )
    {
        
    }
    /* methode qui permet d'afficher la landing page
        on recupere le composant symfony Request
    */
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
    
        $lasts = $this->recetteRepository->findLast(3);
        return $this->render('home/index.html.twig', [

            'last' =>$lasts,
        ]);

    }
}
