<?php

namespace App\Controller;

use App\Form\ChangementProfilType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private SluggerInterface $slugger,private UserRepository $userRepository)
    {
    }

    #[Route('/profile', name: 'profile')]
    public function index(): Response
    {
        
        $user = $this->getUser();
        $recette = $user->getRecettes();

        if($this->isGranted('ROLE_ADMIN')){
            $bannedList = $this->userRepository->findBannedUsers();
         

            return $this->render('profile/index.html.twig', [
                'user' => $user,
                'recettes' =>$recette,
                'bannedList'=>$bannedList,
            ]);
        }
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'recettes' =>$recette,
        ]);
    }

    #[Route('/change_profil', name: 'change_profil')]
    public function change_profil(Request $request,FileUploader $fileUploader): Response
    {
        if(!$this->IsGranted('ROLE_USER')){
            $this->addFlash('danger','Cette action necessite une connexion');
            return $this->redirectToRoute('app_login');
        }
        $form_profil = $this->createForm(ChangementProfilType::class, $this->getUser());
        $form_profil->handleRequest($request);

        
        if ( ($form_profil->isSubmitted() && $form_profil->isValid()) ) {
           $modif_profil = $form_profil->getData();
           $imgFile = $form_profil->get('photo')->getData();
                if ($imgFile) {
                    $imageExistante = $this->getUser()->getPhoto();
                    if($imageExistante){
                        unlink('uploads/'.$imageExistante);
                    }
                    $newFileName = $fileUploader->upload($imgFile);
                    $modif_profil->setPhoto($newFileName);
    
                }
                $this->entityManager->persist($modif_profil);
                $this->entityManager->flush();

                // foreach ($ingredients as $ingredient) {
                //     $recetteHasIngredient->posseder($ingredient, $recette_modifie, 'comme tu veux');
                // }
                $this->addFlash('success', "Le profil a bien ete modifie :)");
                return $this->redirectToRoute('profile');
        }

       
        
        return $this->renderForm('profile/change_profil.html.twig', [
            'form_pseudo'=>$form_profil,
        ]);
    }

    #[Route('/delete_photo', name: 'delete_photo')]
    public function delete_image()
    {
        
        $user = $this->getUser();
        unlink('uploads/'.$user->getPhoto());
        $user->setPhoto(NULL);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $this->addFlash('success','photo supprime');
       return $this->redirectToRoute('profile');
    }

  
}
