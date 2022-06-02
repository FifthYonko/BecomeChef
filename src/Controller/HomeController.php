<?php

namespace App\Controller;

use App\Repository\NotationRepository;
use App\Repository\RecetteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;

class HomeController extends AbstractController
{
    private $requestStack;

    public function __construct(private EntityManagerInterface $entityManager, private RecetteRepository $recetteRepository , private UserRepository $userRepository, RequestStack $requestStack, private NotationRepository $notationRepository )
    {
        $this->requestStack = $requestStack;
    }
    /* methode qui permet d'afficher la landing page
        on recupere le composant symfony Request
    */
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
    
        $lasts = $this->recetteRepository->findLast(3);

        $compterR = $this->recetteRepository->compterRecette();
        $compterU = $this->userRepository->compterUsers();
        $meilleureRecette = $this->notationRepository->meilleureRecette();
        $bestRecette = null;
      if($meilleureRecette){
        $bestRecette = $this->recetteRepository->find($meilleureRecette[0]['id']);
      }
      
       
        
        $session = $this->requestStack->getSession();
        $session->set('Infos',[$compterR,$compterU,$bestRecette]);

        
        return $this->render('home/index.html.twig', [

            'last' =>$lasts,
        ]);
        
        
    }
}
