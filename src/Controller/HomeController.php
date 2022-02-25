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
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $cookie = new Cookie('reponse', 0, strtotime('tomorrow'), '/', 
   'becomeChef.fr', true, true);
        $user = $this->getUser();
        $lasts = $this->recetteRepository->findLast();
        return $this->render('home/index.html.twig', [
            'user' => $user,
            'last' =>$lasts,
        ]);
    }
}
