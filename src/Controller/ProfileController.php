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
    public function __construct(private EntityManagerInterface $entityManager, private SluggerInterface $slugger, private UserRepository $userRepository)
    {
    }
    /*
        methode qui permet d'afficher la page profile de l'utilisateur
        Cette methode ne prend pas de parametres, et redirige vers la page profil
    */
    #[Route('/profile', name: 'profile')]
    public function index(Request $request, FileUploader $fileUploader): Response
    {   // on verifie bien que l'utilisateur est connecte
        if (!$this->IsGranted('ROLE_USER')) {
            // si il ne l'est pas, on affiche un message d'erreur et on redirige vers la page de connexion
            $this->addFlash('danger', 'Cette action necessite une connexion');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        // on recupere les recettes ajoutes par l'utilisateur sur le site
        $recette = $user->getRecettes();
        // on cree un formulaire de modification de profil grace a la classe ChangementProfilType qui se trouve dans le dossier Form
        // on pre-remplis les champs du formulaire avec les informations de l'utilisateur connecte
        $form_profil = $this->createForm(ChangementProfilType::class,$user);
        $form_profil->handleRequest($request);

        // si le formulaire est soumis et les champs sont correctement remplis
        if (($form_profil->isSubmitted() && $form_profil->isValid())) {
            // on recupere les infos et on les stocke dans la variable $modif_profil
            $modif_profil = $form_profil->getData();
            // on recupere le contenu du champ photo
            $imgFile = $form_profil->get('photo')->getData();
            // on verifie que la variable n'est pas vide
            if ($imgFile) {
                // si elle n'est pas vide, on recupere les infos de la photo que l'utilisateur possede deja
                $imageExistante = $this->getUser()->getPhoto();
                // on verifie qu'elle n'est pas vide
                if ($imageExistante) {
                    // si elle existe, on la supprime , comme ca on ne surcharge pas le dossier avec des documents inutiles
                    unlink('uploads/' . $imageExistante);
                }
                // on utilise le service FileUploader afin de modifier les informations du fichier pour le rendre safe, 
                // par exemple on modifie le nom du fichier pour eviter les doublons, ou des noms contenant des caracteres dangereux

                $newFileName = $fileUploader->upload($imgFile);
                // on modifie le champ photo du profil a modifier, et on lui ajoute le nouveau nom de fichier
                $modif_profil->setPhoto($newFileName);
            }
            // on fait les modifs necessaires dans la base de donnees
            $this->entityManager->persist($modif_profil);
            $this->entityManager->flush();

            // foreach ($ingredients as $ingredient) {
            //     $recetteHasIngredient->posseder($ingredient, $recette_modifie, 'comme tu veux');
            // }
            $this->addFlash('success', "Le profil a bien ete modifie :)");
            return $this->redirectToRoute('profile');
        }

        //    si le formulaire n'est pas soumis, on affiche la page contenant le formulaire

        // on  recupere les informations de l'utilisateur et on les stocke dans une variable $user
    
        // on verifie si l'utilisatuer est admin
        if ($this->isGranted('ROLE_ADMIN')) {
            // si oui, on affiche la liste des utilisateurs bannis
            $bannedList = $this->userRepository->findBannedUsers();

            // on redirige vers le template d'affichage
            return $this->renderForm('profile/index.html.twig', [
                'user' => $user,
                'recettes' => $recette,
                'bannedList' => $bannedList,
                'form_pseudo' => $form_profil,
            ]);
        }
        // sinon on affiche juste less informations de l'utilisateur connecte est ses recettes
        return $this->renderForm('profile/index.html.twig', [
            'user' => $user,
            'recettes' => $recette,
            'form_pseudo' => $form_profil,
        ]);
    }
    /* 
        Cette methode permet de modifier les informations du profil de l'utilisateur
        Elle prend en parametres le composant Request et le service FileUploader qui permetra de changer la photo de profil si l'utilisateur le souhaite

    */
    #[Route('/change_profil', name: 'change_profil')]
    public function change_profil(Request $request, FileUploader $fileUploader): Response
    {
    }
    /*
        Methode qui permet de supprimer une photo de profil
        Cette methode ne prend pas d'arguments
    */
    #[Route('/delete_photo', name: 'delete_photo')]
    public function delete_image()
    {
        // on recupere les infos de l'utilisateur
        $user = $this->getUser();
        // on supprime la photo dans le fichier uploads
        unlink('uploads/' . $user->getPhoto());
        // on met le champs photo a NULL
        $user->setPhoto(NULL);
        // on envoie les modifications a la base de donnees
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        // on affiche un message de success, et on redirige vers la page profil
        $this->addFlash('success', 'photo supprime');
        return $this->redirectToRoute('profile');
    }
}
