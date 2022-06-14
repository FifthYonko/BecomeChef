<?php

namespace App\Controller;

use App\Form\CommentaireType;
use App\Form\IngredientFormType;
use App\Form\RecetteFormType;
use App\Repository\CommentaireRepository;
use App\Repository\IngredientRepository;
use App\Repository\NotationRepository;
use App\Repository\PossederRepository;
use App\Repository\RecetteRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    public function __construct(private UserRepository $userRepository, private EntityManagerInterface $entityManager, private RecetteRepository $recetteRepository, private PossederRepository $possederRepository, private CommentaireRepository $commentaireRepository)
    {
    }


    #[Route('admin/espace/{tableau}/{page}', name: 'espaceAdmin')]
    public function index($tableau, int $page, PaginatorInterface $paginator)
    {
        if ($tableau == 'utilisateurs') {
            $utilisateurs = $this->userRepository->findAll();
            $utilisateurs =  $paginator->paginate($utilisateurs, $page, 10);

            return $this->render('admin/espace_admin.html.twig', [
                'utilisateurs' => $utilisateurs,
            ]);
        } elseif ($tableau == 'commentaires') {
            $commentaires = $this->commentaireRepository->findAll();
            $commentaires =  $paginator->paginate($commentaires, $page, 10);
            return $this->render('admin/espace_admin.html.twig', [
                'commentaires' => $commentaires,
            ]);
        } else {
            $recettes = $this->recetteRepository->findAll();
            $recettes =  $paginator->paginate($recettes, $page, 10);

            return $this->render('admin/espace_admin.html.twig', [
                'recettes' => $recettes,
            ]);
        }
    }

    // fonction de ban réservé aux utilisateurs disposant du rôle Admin. 
    // Cette fonction prend en paramètres un entier qui est l'id de l'utilisateur à bannir et ne renvoie aucune donnée, juste une redirection vers une autre page, en fonction des conditions remplies.
    #[Route('admin/ban/{id}', name: 'ban')]
    public function ban(int $id)
    {

        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des privilèges nécessaires');
            return $this->redirectToRoute('home');
        }
        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        } elseif ($user->getId() == $this->getUser()->getId()) {
            $this->addFlash('warning', 'Vous ne pouvez pas vous bannir vous-même');
            return $this->redirectToRoute('home');
        } elseif (in_array('ROLE_SUPER_ADMIN', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous ne disposez pas des droits nécessaires pour cette action');
            return $this->redirectToRoute('home');
        }
        $user->setEtat(1);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->redirectToRoute('home');
    }

  /* Fonction inverse a la fonction ban, elle permet de dé bannir
        elle prend en paramètre un entier, qui représente l'id de l'utilisateur sur qui on veut faire l'action

    */
    #[Route('admin/unban/{id}', name: 'unban')]
    public function unban(int $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des privilèges nécessaires');
            return $this->redirectToRoute('home');
        }
        $user = $this->userRepository->find($id);

        if (!$user) {
            $this->addFlash('warning', 'L\'utilisateur n\'existe pas');
            return $this->redirectToRoute('home');
        } elseif (array_search('ROLE_SUPER_ADMIN', $user->getRoles())) {
            $this->addFlash('warning', 'Vous ne disposez pas des droits nécessaires pour cette action');
            return $this->redirectToRoute('home');
        }
        $user->setEtat(0);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->redirectToRoute('home');
    }

/* La méthode inspecter profil est une méthode reserve aux ceux disposant du droit Admin
        Cette fonction comme son nom l'indique,permet d'inspecter le profil d'un utilisateur autre que soi-même
        Elle prend en paramètre un entier,l'id de l'utilisateur à qui on veut inspecter le profil
        cette méthode ne renvoie rien, elle redirige vers une page
    */
    #[Route('admin/inspect_profil/{id}', name: 'inspect_profil')]
    public function inspect_profil(int $id)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous ne disposez pas des privilèges nécessaires');
            return $this->redirectToRoute('home');
        }
        if ($this->getUser()->getId() === $id) {
            $this->addFlash('success', 'Voici votre profil');
            return $this->redirectToRoute('profile');
        }
        $user = $this->userRepository->find($id);
        return $this->render('admin/inspect_profile.html.twig', [
            'utilisateur' => $user,

        ]);
    }
  /* Cette méthode redirige l'utilisateur disposant du droit admin vers la page catalogue ou on trouve l'ensemble des recettes disponibles
        elle prend en paramètre un entier page, qui représente le nr de page sur lequel on veut aller
    */
    #[Route('/admin/recette/{page}', name: 'recette_admin')]
    public function recetteList(int $page, PaginatorInterface $paginator)
    {

        $recettes = $this->recetteRepository->findAll();

        $recettes =  $paginator->paginate($recettes, $page, 4);

        return $this->render('admin/index_recetteAdmin.html.twig', [
            'recettes' => $recettes,

        ]);
    }


    /* Methode destinee aux admin pour ajouter des nouvelles recettes
        Cette methode instancie le composant symfony Request qui nous permet de gerer les les donnees envoies en post ou en GET
        On recupere aussi le Service FileUploader defini dans le dossier Service. Dans ce service on retrouve les methodes necessaires
        au l'upload de fichiers par l'utilisateur
    
    */
    #[Route('/admin/new_recette', name: 'new_recette_admin')]
    public function ajout_recette(Request $request, FileUploader $fileUploader): Response
    {
        if (!$this->isGranted('ROLE_USER') || !$this->isGranted('ROLE_ADMIN')) {

            $this->addFlash('warning', 'Vous devez être connecter ou être admin pour accéder à cette page!');
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->redirect('home');
        }

        if ($this->getUser()->getEtat() === true) {
            $this->addFlash('danger', 'Vous etes banni et donc vous ne pouvez plus acceder a cette fonctionnalité');
            return $this->redirectToRoute('home');
        }


        $form_recette = $this->createForm(RecetteFormType::class);
        $form_recette->handleRequest($request);
        if ($form_recette->isSubmitted() && $form_recette->isValid()) {
            $new_recette = $form_recette->getData();

            $new_recette->setTitre(ucfirst($form_recette->get('titre')->getData()));
            $new_recette->setPreparation(ucfirst($form_recette->get('preparation')->getData()));

            $imgFile = $form_recette->get('photo')->getData();
            if ($imgFile) {
                $newFileName = $fileUploader->upload($imgFile);
                $new_recette->setPhoto($newFileName);
            } else {
                $new_recette->setPhoto('DefaultPhotoDark.png');
            }

            $date = new DateTime();
            $new_recette->setDate($date);
            $new_recette->setAuthor($this->getUser());
            foreach ($form_recette->get('posseders')->getData() as $ingredient) {
                $ingredient->setRecette($new_recette);
            }
            $this->entityManager->persist($new_recette);
            $this->entityManager->flush();
            $this->addFlash('success', 'La recette a été ajoutée!');
            return $this->redirectToRoute('recette_admin', ['page' => 1]);
        }
        return $this->render('admin/new_recetteAdmin.html.twig', [
            'form_recette' => $form_recette->createView(),
        ]);
    }
   /*
        Route admin pour l'affichage d'une recette,elle prend en paramètre le composant Symfony Request et un entier, cet entier est l'id de la recette à afficher
        Cette méthode renvoie sur une page d'affichage d'une seule recette    
    */
    #[Route('admin/show_recette/{id}/{page}', name: 'show_recette_admin')]
    public function show_recette(Request $request, int $id, int $page, NotationRepository $notationRepository, PaginatorInterface $paginator)
    {
        $recette = $this->recetteRepository->findOneBy(['id' => $id]);
        $commentaires = $recette->getCommentaires();
        $commentaires = $paginator->paginate($commentaires, $page, 2);

         // moyenne de la note 
        $moy = round($notationRepository->moyenneNotation($id), 1);

        // on vérifie si l'utilisateur a noté ou pas la recette car un utilisateur peut noter une seule fois une recette
        $notee = $notationRepository->verifierNotation($id, $this->getUser()) != null;

        if ($this->IsGranted('ROLE_ADMIN')) {
            $form_comm = $this->createForm(CommentaireType::class);
            $form_comm->handleRequest($request);

            if ($form_comm->isSubmitted() && $form_comm->isValid()) {
                $new_comm = $form_comm->getData();
                $new_comm->setAuthor($this->getUser());
                $new_comm->setRecette($recette);

                $this->entityManager->persist($new_comm);
                $this->entityManager->flush();
                $this->addFlash('success', 'Le commentaire a été ajouté');
                return $this->redirectToRoute('show_recette_admin', ['id' => $id, 'page' => 1]);
            }
            return $this->renderForm('admin/show_recetteAdmin.html.twig', [
                'recette' => $recette,
                'comments' => $commentaires,
                'form_comm' => $form_comm,
                'note' => $moy,
                'notee' => $notee,
            ]);
        }

        return $this->renderForm('admin/show_recetteAdmin.html.twig', [
            'recette' => $recette,
            'comments' => $commentaires,
            'note' => $moy,
            'notee' => $notee,

        ]);
    }

    /*
        Méthode admin pour modifier les recettes existantes.
        Cette méthode prend en paramètres le composant Symfony Request, un entier id qui représente l'id de la recette à modifier, et le service File Uploader
        Elle renvoie une redirection vers le catalogue des recettes si tout se passe bien
    */
    #[Route('admin/update_recette/{id}', name: 'update_recette_admin')]
    public function update_recette(Request $request, int $id, FileUploader $fileUploader)
    {

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }

        $recette_modifie = $this->recetteRepository->find($id);

        if (!$recette_modifie) {
            $this->addFlash('warning', 'Aucune recette trouvée');
        }
        $imageExistante = $recette_modifie->getPhoto();
        if ($this->isGranted('ROLE_ADMIN')) {
            $update_recette = $this->createForm(RecetteFormType::class, $recette_modifie);
            $update_recette->handleRequest($request);

            if ($update_recette->isSubmitted() && $update_recette->isValid()) {
     
                $recette_modifie = $update_recette->getData();
                foreach ($update_recette->get('posseders')->getData() as $ingredient) {

                    $ingredient->setRecette($recette_modifie);
                }
                $imgFile = $update_recette->get('photo')->getData();
                if ($imgFile) {
                    if ($imageExistante && $imageExistante != "DefaultPhotoDark.png") {
                        unlink('uploads/' . $imageExistante);
                    }
                    $newFileName = $fileUploader->upload($imgFile);
                    $recette_modifie->setPhoto($newFileName);
                }
                $this->entityManager->persist($recette_modifie);
                $this->entityManager->flush();


                $this->addFlash('success', "La recette a bien été modifiée :)");
                return $this->redirectToRoute('recette_admin', ['page' => 1]);
            }
        } else {
            if ($this->isGranted('ROLE_USER')) {
                $this->addFlash("danger", "Vous devez être Admin ou le propriétaire de la recette pour la modifier!");
                return $this->redirectToRoute('recette', ['page' => 1]);
            } else {
                $this->addFlash("danger", "Vous devez être soit connecter soit Admin ou le propriétaire de la recette pour la modifier!");
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('admin/update_recetteAdmin.html.twig', [
            'update_recette' => $update_recette->createView(),
        ]);
    }

    /* Méthode admin pour supprimer une recette 
        Elle prend en paramètre un entier id qui représente l'id de la recette à supprimer
    */
    #[Route('admin/delete_recette/{id}', name: 'delete_recette_admin')]
    public function delete_recette(int $id)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Connectez-vous pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $recette_aSupp = $this->recetteRepository->find($id);
            if (!$recette_aSupp) {
                $this->addFlash('warning', 'Aucune recette trouvée');
                return $this->redirectToRoute('recette_admin', ['page' => 1]);
            }
            $imgFile = 'uploads/' . $recette_aSupp->getPhoto();
            if ($imgFile) {
                unlink($imgFile);
            }
            $this->entityManager->remove($recette_aSupp);
            $this->entityManager->flush();
            $this->addFlash('success', "La recette a bien été supprimée!");
            return $this->redirectToRoute('recette_admin', ['page' => 1]);
        } elseif (!$this->isGranted("ROLE_ADMIN")) {
            $this->addFlash('warning', "Vous ne pouvez pas supprimer cette recette car vous n'êtes ni admin, ni l'auteur");
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
    }

   /* fonction qui supprime un ingrédient de la liste d'ingrédients d'une recette.
        accessible à partir de la page d'affichage d'une recette
        Prends en paramètre un entier qui représente l'id de l'enregistrement à supprimer
        Cette fonction redirige vers la vue d'une recette
    */
    #[Route('admin/delete_ingredient/{id}', name: 'delete_ingredient_admin')]

    public function delete_ingredient(int $id)
    {
        if (!$this->IsGranted('ROLE_USER')) {
            $this->addFlash('danger', 'Vous devez vous connecter pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }
        $relationaSupp = $this->possederRepository->find($id);

        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous devez être admin ou propriétaire de la recette pour effectuer cette tâche');
            return $this->redirectToRoute('show_recette', ['id' => $relationaSupp->getRecette()->getId(),'page' => 1]);
        }
        $this->entityManager->remove($relationaSupp);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ingredient retire');
        return $this->redirectToRoute('show_recette_admin', ['id' => $relationaSupp->getRecette()->getId(),'page' => 1]);
    }

    /**
     * Méthode qui permet à un admin de supprimer un commentaire pour une recette donnée
     */
    #[Route('admin/delete_commentaire/{id}/{idR}', name: 'delete_commentaire_admin')]
    public function delete_commentaire(int $id, int $idR, CommentaireRepository $commentaireRepository, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Connectez-vous pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }
        $comment_aSupp = $commentaireRepository->find($id);
        if (!$comment_aSupp) {
            $this->addFlash('warning', 'Aucun commentaire trouvé');
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            $entityManager->remove($comment_aSupp);
            $entityManager->flush();
            $this->addFlash('success', 'Le commentaire a bien été supprimé');
            return $this->redirectToRoute('show_recette_admin', ['id' => $idR,'page' => 1]);
        } else {
            $this->addFlash('warning', 'Vous ne disposez pas de ces droits');
            return $this->redirectToRoute('show_recette', ['id' => $idR,'page' => 1]);
        }
    }

    /**
     * Méthode réservée aux admins pour ajouter un nouvel ingrédient à la liste d'ingrédients du site
     */

    #[Route('admin/ajout_ingredient', name: 'ajoutIngredients')]
    public function ajout_ingredient(Request $request, FileUploader $fileUploader,IngredientRepository $ingredientRepository)
    {
        if (!$this->isGranted('ROLE_USER') || !$this->isGranted('ROLE_ADMIN')) {

            $this->addFlash('warning', 'Vous devez être connecter ou être admin pour accéder à cette page!');
            return $this->redirectToRoute('app_login');
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->redirect('home');
        }
        $form_ingredient = $this->createForm(IngredientFormType::class);
        $form_ingredient->handleRequest($request);

        if ($form_ingredient->isSubmitted() && $form_ingredient->isValid() ) {
            $new_ingredient = $form_ingredient->getData();
           
            if(empty($ingredientRepository->findBy(['nom'=>$new_ingredient->getNom()])) == false){
                $this->addFlash('warning','L\'ingrédient existe déjà !');
                return $this->render('admin/new_IngredientAdmin.html.twig', [
                    'form_ingredient' => $form_ingredient->createView(),
                    
                ]);
            }
            $imgFile = $form_ingredient->get('photo')->getData();
            if ($imgFile) {
                $newFileName = $fileUploader->upload($imgFile);
                $new_ingredient->setPhoto($newFileName);
            } else {
                $new_ingredient->setPhoto('DefaultPhotoDark.png');
            }
            $this->entityManager->persist($new_ingredient);
            $this->entityManager->flush();
            $this->addFlash('success', 'L\'ingrédient a été ajouté !');
            return $this->redirectToRoute('recette_admin', ['page' => 1]);
          
        }

        return $this->render('admin/new_IngredientAdmin.html.twig', [
            'form_ingredient' => $form_ingredient->createView(),
            
        ]);
    }
}
