<?php

namespace App\Controller;


use App\Form\CommentaireType;
use App\Form\RecetteFormType;
use App\Repository\PossederRepository;
use App\Repository\RecetteRepository;
use App\Service\FileUploader;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class RecetteController extends AbstractController
{
    public function __construct(private RecetteRepository $recetteRepository, private PossederRepository $possederRepository, private EntityManagerInterface $entityManager, private SluggerInterface $slugger)
    {
    }
   
    /*
        Methode d'affichage de recettes. Elle prend en parametre un entier $page .
        Elle redirige vers la page d'affichage des recettes disponibles sur le site
    */
    #[Route('/recette/{page}', name: 'recette')]
    public function index(int $page,PaginatorInterface $paginator)
    {

        $recettes = $this->recetteRepository->findAll();
       $recettes =  $paginator->paginate($recettes,$page,2);
        return $this->render('recette/index.html.twig', [
            'recettes' => $recettes,
            
        ]);
    }

    #[Route('/new_recette', name: 'new_recette')]
    /**
     * Methode qui permet d'ajouter des recettes. Elle prend en parametre, le composant Request et le service FileUploader
     */
    public function ajout_recette(Request $request, FileUploader $fileUploader): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            $this->addFlash('warning', 'Vous devez etre connecte pour acceder a cette page!');
            return $this->redirectToRoute('app_login');
        }
        if ($this->getUser()->getEtat() === true) {
            $this->addFlash('danger', 'Vous etes banni et donc vous ne pouvez plus acceder a cette fonctionnalite');
            return $this->redirectToRoute('home');
        }
        $form_recette = $this->createForm(RecetteFormType::class);
        $form_recette->handleRequest($request);
        if ($form_recette->isSubmitted()&& $form_recette->isValid() ) {
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
            $new_recette->setAuthor($this->getUser());
            $date = new DateTime();
            $new_recette->setDate($date);
            foreach ($form_recette->get('posseders')->getData() as $ingredient) {
                $ingredient->setRecette($new_recette);
            }
            $this->entityManager->persist($new_recette);
            $this->entityManager->flush();

            $this->addFlash('success', 'La recette a ete ajoute !');
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
        return $this->render('recette/new_recette.html.twig', [
            'form_recette' => $form_recette->createView(),
        ]);
    }
    /*
        Route utilisateur pour l'affichage d'une recette , elle prend en parametre le composant symfony Request et un entier, cet entier est l'id de la recette a afficher
        Cette methode renvoie sur une page d'affichage d'une seule recette    
    */
    #[Route('/show_recette/{id}', name: 'show_recette')]
    public function show_recette(Request $request, int $id)
    {
       
        $recette = $this->recetteRepository->findOneBy(['id' => $id]);
        $commentaires = $recette->getCommentaires();

        if ($this->IsGranted('ROLE_USER') && $this->getUser()->getEtat() != 1) {
            $form_comm = $this->createForm(CommentaireType::class);
            $form_comm->handleRequest($request);

            if ($form_comm->isSubmitted() && $form_comm->isValid()) {

                $new_comm = $form_comm->getData();
                $new_comm->setAuthor($this->getUser());
                $new_comm->setRecette($recette);

                $this->entityManager->persist($new_comm);
                $this->entityManager->flush();
                $this->addFlash('success', 'Le commentaire a ete ajoute');
                return $this->redirectToRoute('show_recette', ['id' => $id]);
            }

            return $this->renderForm('recette/show_recette.html.twig', [
                'recette' => $recette,
                'comments' => $commentaires,
                'form_comm' => $form_comm,
            ]);
        }


        return $this->renderForm('recette/show_recette.html.twig', [
            'recette' => $recette,
            'comments' => $commentaires,

        ]);
    }
    /*
        methode utilisateur pour modifier les recettes existantes.
        Cette methode prend en parametres le composant symfony Request, un entier id qui represente l'id de la recette a modifier, et le service FileUploader
        Elle renvoie une redirection vers le catalogue des recettes si tout se passe bien
    */
    #[Route('/update_recette/{id}', name: 'update_recette')]
    public function update_recette(Request $request, int $id, FileUploader $fileUploader)
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour accéder à cette fonctionnalité');
            return $this->redirectToRoute('app_login');
        }
        $recette_modifie = $this->recetteRepository->find($id);

        if (!$recette_modifie) {
            $this->addFlash('warning', 'Aucune recette trouve');
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
        $imageExistante = $recette_modifie->getPhoto();
        if ($user->getId() === $recette_modifie->getAuthor()->getId()) {
            $update_recette = $this->createForm(RecetteFormType::class, $recette_modifie);
            $update_recette->handleRequest($request);
            if ($update_recette->isSubmitted() && $update_recette->isValid()) {

                $recette_modifie = $update_recette->getData();
                $recette_modifie->setTitre(ucfirst($update_recette->get('titre')->getData()));
                $recette_modifie->setPreparation(ucfirst($update_recette->get('preparation')->getData()));
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

                $this->addFlash('success', "La recette a bien ete modifie :)");
                return $this->redirectToRoute('recette', ['page' => 1]);
            }
        } else {
            if ($this->isGranted('ROLE_USER')) {
                $this->addFlash("danger", "Vous devez etre Admin ou le proprietaire de la recette pour la modifier!");
                return $this->redirectToRoute('recette', ['page' => 1]);
            }
        }
        return $this->render('recette/update_recette.html.twig', [
            'update_recette' => $update_recette->createView(),
        ]);
    }


    /* Methode admin pour supprimer une recette 
    *  Elle prend en parametre un entier id qui represente l'id de la recette a supprimer
    */
    #[Route('/delete_recette/{id}', name: 'delete_recette')]
    public function delete_recette(Request $request, int $id)
    {

        $user = $this->getUser();

        if (!$user) {

            $this->addFlash('danger', 'Connectez-vous pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }

        if ($user === $recette_aSupp->getAuthor()) {
            $recette_aSupp = $this->recetteRepository->find($id);
            if (!$recette_aSupp) {

                $this->addFlash('warning', 'Aucune recette trouve');
            }

            $imgFile = 'uploads/' . $recette_aSupp->getPhoto();

            if ($imgFile) {
                unlink($imgFile);
            }

            $this->entityManager->remove($recette_aSupp);
            $this->entityManager->flush();

            $this->addFlash('success', "La recette a bien ete supprime!");
            return $this->redirectToRoute('recette', ['page' => 1]);


        } elseif ($user === $recette_aSupp->getAuthor() || !$this->isGranted("ROLE_ADMIN")) {

            $this->addFlash('warning', "Vous ne pouvez pas supprimer cette recette car vous n'etez ni admin, ni auteur");
            return $this->redirectToRoute('recette', ['page' => 1]);
        }
    }

       /**
     * Methode de recherche dans la base de donnes 
     */
  
    #[Route('/search/{page}', name: 'search')]

    public function search(int $page, Request $request, PaginatorInterface $paginator)
    {

       $valeurs = explode(' ',$request->query->get('search_value'));
        $recettes = array();
       for ($i=0; $i < count($valeurs); $i++) { 
        $resultats  = $this->recetteRepository->findByExampleField($valeurs[$i],$page-1,count($valeurs));
           for ($j=0; $j < count($resultats) ; $j++) { 
            array_push($recettes,$resultats[$j]);
            
           }
       }
        if(empty($recettes)){

            $recettes = "Nous n'avons rien trouvé , veuillez essayer autre chose";
        }
        
        $recettes =  $paginator->paginate($recettes,$page,3);
       
        return $this->render('recette/search.html.twig', [
            'recettes' => $recettes,
           
        ]);
    }

  
    /* fonction qui supprime un ingredient de la liste d'ingredients d'une recette.
        accessible a partir de la page d'affichage d'une recette
        prend en parametre un entier qui represente l'id de l'enregistrement a supprimer
        Cette fonction redirige vers la vue d'une recette
    */
    #[Route('/delete_ingredient/{id}', name: 'delete_ingredient')]

    public function delete_ingredient(int $id)
    {
        if (!$this->IsGranted('ROLE_USER')) {
            $this->addFlash('danger', 'vous devez vous connecter pour acceder a cette fonctionnalite');
            return $this->redirectToRoute('app_login');
        }
        $relationaSupp = $this->possederRepository->find($id);
        $proprietaire = $relationaSupp->getRecette()->getAuthor()->getId();

        if (!$this->getUser()->getId() != $proprietaire) {
            $this->addFlash('warning', 'vous devez etre admin ou proprietaire de la recette pour effectuer cette tache');
            return $this->redirectToRoute('show_recette', ['id' => $relationaSupp->getRecette()->getId()]);
        }
        $this->entityManager->remove($relationaSupp);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ingredient retire');
        return $this->redirectToRoute('show_recette', ['id' => $relationaSupp->getRecette()->getId()]);
    }
}
