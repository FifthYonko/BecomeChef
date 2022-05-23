<?php

namespace App\Controller;

use App\Form\ChangementProfilType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager, private SluggerInterface $slugger, private UserRepository $userRepository)
    {
    }
    /*
        methode qui permet d'afficher la page profile de l'utilisateur
        Cette methode ne prend pas de parametres, et redirige vers la page profil
    */
    #[Route('/profile/{page}', name: 'profile')]
    public function index(Request $request,int $page=1, FileUploader $fileUploader,PaginatorInterface $paginator): Response
    {   
        if (!$this->IsGranted('ROLE_USER')) {
            $this->addFlash('danger', 'Cette action necessite une connexion');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $recette = $user->getRecettes();
        $recette =  $paginator->paginate($recette,$page,3);

        $form_profil = $this->createForm(ChangementProfilType::class,$user);
        $form_profil->handleRequest($request);

        if (($form_profil->isSubmitted() && $form_profil->isValid())) {
            $modif_profil = $form_profil->getData();
            $imgFile = $form_profil->get('photo')->getData();
            if ($imgFile) {
                $imageExistante = $this->getUser()->getPhoto();
                if ($imageExistante) {
                    unlink('uploads/' . $imageExistante);
                }

                $newFileName = $fileUploader->upload($imgFile);
                $modif_profil->setPhoto($newFileName);
            }
            $this->entityManager->persist($modif_profil);
            $this->entityManager->flush();

            $this->addFlash('success', "Le profil a bien ete modifie :)");
            return $this->redirectToRoute('profile',['page'=>1]);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            
            $bannedList = $this->userRepository->findBannedUsers();
          

          
            return $this->renderForm('profile/index.html.twig', [
                'user' => $user,
                'recettes' => $recette,
                'bannedList' => $bannedList,
                'form_pseudo' => $form_profil,
            ]);
        }
      
        return $this->renderForm('profile/index.html.twig', [
            'user' => $user,
            'recettes' => $recette,
            'form_pseudo' => $form_profil,
        ]);
    }
 
    /*
        Methode qui permet de supprimer une photo de profil
        Cette methode ne prend pas d'arguments
    */
    #[Route('/delete_photo', name: 'delete_photo')]
    public function delete_image()
    {
        $user = $this->getUser();
        unlink('uploads/' . $user->getPhoto());
        $user->setPhoto(NULL);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->addFlash('success', 'photo supprime');
        return $this->redirectToRoute('profile',['page'=>1]);
    }
}
