<?php

namespace App\Controller;


use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private SluggerInterface $slugger)
    {
    }

    #[Route('/profile', name: 'profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        $recette = $user->getRecettes();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'recettes' =>$recette,
        ]);
    }

    #[Route('/change_profil', name: 'change_profil')]
    public function change_profil(Request $request): Response
    {
        $form_pseudo = $this->createForm(RegistrationFormType::class, $this->getUser());
        $form_pseudo->handleRequest($request);

       
        
        return $this->renderForm('profile/change_profil.html.twig', [
            'form_pseudo'=>$form_pseudo,
        ]);
    }
   
    
}
